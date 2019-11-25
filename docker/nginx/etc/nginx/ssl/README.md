#Generate self signed certificates

    openssl genrsa -out default.key 2048
    openssl req -new -out default.csr -key default.key -config openssl.cnf
    openssl x509 -req -days 3650 -in default.csr -signkey default.key -out default.crt -extensions v3_req -extfile openssl.cnf
