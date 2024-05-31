#!/bin/bash

RESPONSE=$(curl -s -o /dev/stderr -w "%{http_code}" -X POST -H "Content-Type: application/json" \
-d '{"repository":{"url":"https://github.com/Airalo/airalo-php-sdk"}}' \
"https://packagist.org/api/update-package?username=$PACKAGIST_USER&apiToken=$PACKAGIST_TOKEN" 2>&1)

# extract http code only
HTTP_CODE=${RESPONSE: -3}

if [[ "$HTTP_CODE" -ne 202 ]]; then
    echo "Packagist update failed. Response: $RESPONSE."

    exit 1
fi

echo "Packagist update succeeded."
