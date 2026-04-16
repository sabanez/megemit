#!/bin/bash
set -x # Verbose mode.

# Check if QIT_USER and QIT_APP_PASSWORD are set and not empty
if [[ -z "${QIT_USER}" ]] || [[ -z "${QIT_APP_PASSWORD}" ]]; then
    echo "QIT_USER or QIT_APP_PASSWORD environment variables are not set or empty. Please set them before running the script."
    exit 1
fi

# When QIT is run for the first time, it will prompt for onboarding. this will disable that prompt.
export QIT_DISABLE_ONBOARDING=yes

# If QIT_BINARY is not set, default to ./vendor/bin/qit
QIT_BINARY=${QIT_BINARY:-./vendor/bin/qit}

# Check if 'partner:remove' command is in the list of available commands
if ! $QIT_BINARY list | grep -q "partner:remove"; then
    echo "Adding partner with QIT_USER and QIT_APP_PASSWORD..."
    $QIT_BINARY partner:add --user="${QIT_USER}" --application_password="${QIT_APP_PASSWORD}"
    if [ $? -ne 0 ]; then
        echo "Failed to add partner. Exiting with status 1."
        exit 1
    fi
fi

# Run the security command
echo "Running security command..."
$QIT_BINARY run:security woocommerce-dynamic-pricing --zip=./../dist/woocommerce-dynamic-pricing.zip --wait
if [ $? -ne 0 ]; then
    echo "Failed to run security command. Exiting with status 1."
    exit 1
fi
