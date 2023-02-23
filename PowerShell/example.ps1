import-module PKCE  
import-module JWTDetails

$clientID = 'put-client-id-here'
$clientSecret = 'put-secret-here'
$replyURL = 'https://localhost/'
$scopes = ''

Function Get-AuthCode {
    [cmdletbinding()]
    param(
        [Parameter(Mandatory = $true, ValueFromPipeline = $true)]
        [string]$url 
    )

    Add-Type -AssemblyName System.Windows.Forms
    $form = New-Object -TypeName System.Windows.Forms.Form -Property @{Width = 440; Height = 640 }
    $web = New-Object -TypeName System.Windows.Forms.WebBrowser -Property @{Width = 420; Height = 600; Url = ($url -f ($Scope -join "%20")) }
    $DocComp = {
        $uri = $web.Url.AbsoluteUri        
        if ($uri -match "error=[^&]*|code=[^&]*") { $form.Close() }
    }

    $web.ScriptErrorsSuppressed = $true
    $web.Add_DocumentCompleted($DocComp)
    $form.Controls.Add($web)
    $form.Add_Shown( { $form.Activate() })
    $form.ShowDialog() | Out-Null
    $queryOutput = [System.Web.HttpUtility]::ParseQueryString($web.Url.Query)
    
    $output = @{}
    foreach ($key in $queryOutput.Keys) {
        $output["$key"] = $queryOutput[$key]
    }
    return $output
}

$codeChallenge, $codeVerifier, $authResult = $null 
$pkceCodes = New-PKCE 
$codeChallenge = $pkceCodes.code_challenge
$codeVerifier = $pkceCodes.code_verifier
$state = $codeChallenge.Substring(0, 27)

$url = "https://login.daisycon.com/oauth/authorize?response_type=code&redirect_uri=$($replyURL)&client_id=$($clientID)&state=$($state)&code_challenge=$($codeChallenge)"

$authResult = Get-AuthCode -url $url 

if ($authResult.code) {
    Write-Output "Received an authorization code. $($authResult.code)"
    $authCode = $authResult.code 

    $tokenParams = @{
        grant_type    = "authorization_code";
        client_id     = $clientID;
        client_secret = $clientSecret; 
        code          = $authCode;
        code_verifier = $codeVerifier;
        redirect_uri  = $replyURL;
    }

    $tokenResponse = Invoke-RestMethod -Method Post `
        -Uri "https://login.daisycon.com/oauth/access-token" `
        -Body $tokenParams -ContentType 'application/x-www-form-urlencoded' 

    Write-Output $tokenResponse
    Get-JWTDetails $tokenResponse.access_token

    # Example api call
    $apiCallResponse = Invoke-RestMethod -Headers @{Authorization = "Bearer $($tokenResponse.access_token)" } `
        -Uri 'https://services.daisycon.com/categories' `
        -Method Get `
        -ContentType "application/json"

    Write-Output $apiCallResponse | ConvertTo-Json
}
else {
    Write-Error $_
}
