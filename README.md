# Eliza - Logging Server


## Server requirements

* Apache (.htaccess)
* PHP 7


## Hot to deploy

1. Install composer (if needed):

    ```
    brew install composer
    ```
    
2. Install dependencies:

    ```
    composer install
    ```
    
    This will also run the post install script 
    `post_install.sh`
    
3. Deploy the contents of `api/` to your web server.


## Usage (API Docs)

* [GET /](#get-)
* [GET /session](#get-session)
* [GET /log](#get-log)


### **GET /**
Get API information.

Query parameters:
* None

Returns:
* 200 `{ name, description, version, git_sha }`

Errors:
* None


### **GET /session**
Start a new logging session.

Query parameters:
* None

Returns:
* 200 `{ session }`
    * `session`: Session token, used to authenticate subsequent calls to [/log](#get-log)

Errors:
* None


### **GET /log**
Add message to a logging session. The session token needs to be provided in one of the following ways:
* Using an `Authorization: Bearer <session_token>` header
* Using the `session` query parameter

Query parameters:
* `message`: (Required) The message to be added to the log.
* `session`: (Optional) Session token, retrieved from [/session](#get-session). If this is omitted, an `Authorization` header containing the session token needs to be sent.

Returns:
* 200

Errors:
* 400 `{error: 'message required'}`: `message` parameter is missing
* 401 `{error: 'session required'}`: no session token was provided
* 403 `{error: 'invalid session'}`: session token is invalid 
* 403 `{error: 'session expired'}`: session token is expired
