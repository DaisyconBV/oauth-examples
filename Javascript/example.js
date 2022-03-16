const clientId = '';
const redirectUri = '';

// Start: PKCE challenge request
const codeVerifier = generateRandomString(128);
sessionStorage.setValue('code_verifier', codeVerifier);

const codeChallenge = await generateCodeChallenge(codeVerifier);

const requestParams = new URLSearchParams({
	response_type: 'code',
	client_id: clientId,
	code_challenge_method: 'S256',
	code_challenge: codeChallenge,
	redirect_uri: redirectUri
});
document.location = `https://login.daisycon.com.local/oauth/authorize?${requestParams.toString()}`;

async function generateCodeChallenge(codeVerifier) {
	let digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(codeVerifier));

	return btoa(String.fromCharCode(...new Uint8Array(digest)))
		.replace(/=/g, '')
		.replace(/\+/g, '-')
		.replace(/\//g, '_');
}

function generateRandomString(length) {
	let randomString = '';
	let allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	for (let charNumber= 0; charNumber < length; ++charNumber) {
		randomString += allowedChars.charAt(Math.floor(Math.random() * allowedChars.length));
	}
	return randomString;
}



// Authorized redirect URI code
const urlSearchParams = new URLSearchParams(window.location.search);
const queryParams = Object.fromEntries(urlSearchParams.entries());

try
{
	const data = {
		grant_type: 'authorization_code',
		code: queryParams.code,
		client_id: clientId,
		client_secret: '', // leave empty
		redirect_uri: redirectUri,
		code_verifier: sessionStorage.getItem('code_verifier')
	};

	const response = await fetch(
		'https://login.daisycon.com/oauth/access-token',
		{
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		}
	);
	console.log(response.json());
} catch (e) {
	console.error(e);
}
