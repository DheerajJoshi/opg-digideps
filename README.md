#Complete the Deputy Report (Api)

beta version

Auth
----
via    /auth/login: 

Needs Client token header and credentials, responds with AuthToken to send for subsequent requests.

Some endpoints are open in the firewall for special functionalities without being logged. 
Client secret is required for those.
    

Return codes
------------
* 404 not found
* 403 Missing client secret, or invalid permissions (configuration error)
* 419 AuthToken missing, expired or not matching (runtime error)
* 498 wrong credentials at login
* 500 generic error due to internal exception (e.g. db offline)

