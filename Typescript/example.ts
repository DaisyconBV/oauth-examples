const clientId: string = '';
const redirectUri: string = '';



enum GrantTypeEnum {
	AUTHORIZATION_CODE = 'authorization_code',
	REFRESH_TOKEN = 'refresh_token'
}

interface AuthCodeInterface {
	grant_type: GrantTypeEnum;
	code?: string;
	refresh_token?: string;
	client_id: string;
	client_secret: string;
	redirect_uri: string;
	code_verifier: string;
	state?: string;
}

interface AuthCodeResponseInterface {
	code: string;
	state?: string;
}

interface AccessTokenResponseInterface {
	access_token: string;
	refresh_token: string;
}


// Start: PKCE challenge request
const codeVerifier: string = generateRandomString(128);
sessionStorage.setValue('code_verifier', codeVerifier);

const codeChallenge: string = await generateCodeChallenge(codeVerifier);

const requestParams: URLSearchParams = new URLSearchParams({
	response_type: 'code',
	client_id: clientId,
	code_challenge_method: 'S256',
	code_challenge: codeChallenge,
	redirect_uri: redirectUri
});
document.location = `https://login.daisycon.com/oauth/authorize?${requestParams.toString()}`;

async function generateCodeChallenge(codeVerifier): string {
	let digest: ArrayBuffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(codeVerifier));

	return btoa(String.fromCharCode(...new Uint8Array(digest)))
		.replace(/=/g, '')
		.replace(/\+/g, '-')
		.replace(/\//g, '_');
}

function generateRandomString(length): string {
	let randomString: string = '';
	let allowedChars: string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	for (let charNumber= 0; charNumber < length; ++charNumber) {
		randomString += allowedChars.charAt(Math.floor(Math.random() * allowedChars.length));
	}
	return randomString;
}

// Authorized redirect URI code
const urlSearchParams: URLSearchParams = new URLSearchParams(window.location.search);
const queryParams: AuthCodeResponseInterface = Object.fromEntries(urlSearchParams.entries());

try
{
	const data: AuthCodeInterface = {
		grant_type: GrantTypeEnum.AUTHORIZATION_CODE,
		code: queryParams.code,
		client_id: clientId,
		client_secret: '', // leave empty
		redirect_uri: redirectUri,
		code_verifier: sessionStorage.getItem('code_verifier')
	};

	const response: Response = await fetch(
		'https://login.daisycon.com/oauth/access-token',
		{
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		}
	);
	const tokens: AccessTokenResponseInterface = <any>response.json();
	console.log(tokens);
} catch (e) {
	console.error(e);
}
