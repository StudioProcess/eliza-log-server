#!/usr/bin/env sh

cp composer.json api/

git rev-parse HEAD > api/git-sha

# create a jwt secret if one doesn't already exist
if [ ! -f api/jwt.secret ]; then
  openssl rand -base64 256 > api/jwt.secret
fi
