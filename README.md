# oAuth examples

This repository contains small example scripts for oAuth usage.


# Javascript/Typescript

Note: both javascript and typescript examples assume you use a compiler like webpack

We also have an oauth client package available with cli support
https://github.com/DaisyconBV/js-oauth-client


# PHP 

Even though you can see our examples here, we recommend you use the PHP League oAuth Client
https://github.com/thephpleague/oauth2-client

For CLI there is a CLI client available.

Usage

```shell
php PHP/cli-client.php --clientId CLIENT_ID --clientSecret CLIENT_SECRET --outputFile tokens.json
```

You can then use the tokens in your code and refresh them using your own code
