# Eliza - Logging Server


## Server requirements

* Apache (.htaccess)
* PHP 7


## How to deploy

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
Add message to a logging session. One of the parameters `message` or `messages` is required. If both are given, `messages` is ignored.  
The session token needs to be provided in one of the following ways:
* Using an `Authorization: Bearer <session_token>` header
* Using the `session` query parameter

Query parameters:
* `message`: (Optional) The message to be added to the log.
* `messages`: (Optional) Array of messages to be added to the log. JSON encoded array of one or more strings e.g. `messages=["first msg","second msg"]` (use `JSON.stringify()` or similar).
* `session`: (Optional) Session token, retrieved from [/session](#get-session). If this is omitted, an `Authorization` header containing the session token needs to be sent.

Returns:
* 200

Errors:
* 400 `{error: 'message(s) required'}`: no `message` or `messages` parameter was provided
* 400 `{error: 'messages needs to be a JSON array of one or more strings'}`: `messages` parameter is in wrong format
* 400 `{error: 'message(s) exceeds max length'}`: `message` or `messages` parameter too long
* 401 `{error: 'session required'}`: no session token was provided
* 403 `{error: 'invalid session'}`: session token is invalid 
* 403 `{error: 'session expired'}`: session token is expired
