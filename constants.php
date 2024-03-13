<?php
include_once 'local_config.php';

// Constants
   define('SESSION_TIMEOUT',1800);
   define('ROOT_PATH','cwc_react');
   define('LOGGING_DIRECTORY',CONFIG_HOMEDIR."/cwc_logs/");
   define('ERRORSTEM','wammmeuk_');
   define('MAX_INVALID_LOGIN_ATTEMPTS',5);

// Database constants
   define('NO_ROWS_FOUND',1329);

// Database errors
   define('ERR_UNKNOWN_USER',              1);
   define('ERR_INVALID_PASSWORD',          9);
   define('ERR_UNREGISTERED_USER',        10);
   define('ERR_RETURNED_EMAIL',           11);
   define('ERR_INVALID_REGISTRATION_KEY', 12);
   define('ERR_ALREADY_REGISTERED',       13);

   define('ERR_INTERNAL_ERROR',            1000);

// Returned errors
   define('UNKNOWN_USER',          'UnknownUser');
   define('REGISTERED_USER',       'RegisteredUser');
   define('EXISTING_NAME',         'ExistingName');
   define('INVALID_PASSWORD',      'InvalidPassword');
   define('ACCOUNT_LOCKED',        'AccountLocked');
   define('INVALID_RESET_UUID',    'InvalidResetUUID');
   define('ACCOUNT_NOT_ACTIVATED', 'AccountNotActivated');
   define('INVALID_EMAIL',         'InvalidEmail');
   define('INVALID_CAPTCHA',       'InvalidCaptcha');

// Information messages
   define('INFO_NEW_PASSWORD_SUBJECT',          'Password Change');
   define('INFO_NEW_PASSWORD',                  'Your new password is {PASSWORD}');
   define('INFO_NEW_PASSWORD_STATUS',           'Your password has been changed - please check your email for the new password');
   define('INFO_COMPLETE_REGISTRATION_SUBJECT', 'Complete Registration');
   define('INFO_COMPLETE_REGISTRATION',         'Please visit {URL} to complete registration');
   define('INFO_COMPLETE_REGISTRATION_STATUS',  'Please check your email for the details on how to complete registration');
   define('INFO_REGISTRATION_COMPLETE',         'Registration complete');
   define('INFO_EMAIL_SENT',                    'Thank you for your comments');

// Error messages
   define('MSG_ERR_BAD_LOGON',              'Invalid email/password combination - please try again');
   define('MSG_ERR_BAD_USERID',             'Unknown email - please try again');
   define('MSG_ERR_BOUNCED_EMAIL',          'Invalid email - please try again');
   define('MSG_ERR_ALREADY_REGISTERED',     'This user is already registered');
   define('MSG_ERR_INVALID_EMAIL_OR_KEY',   'Invalid email or registration key - please try again');
   define('MSG_ERR_INVALID_DATE',           'Invalid date - please reenter');
   define('MSG_ERR_ADMIN_USER_ONLY',        'This function is only available to administration users');
   define('MSG_ERR_EMAIL_NAME_NOT_SET',     'Please enter your name');
   define('MSG_ERR_EMAIL_ADDR_NOT_SET',     'Please enter your email address');
   define('MSG_ERR_EMAIL_COMMENTS_NOT_SET', 'Please enter your comments');
   define('MSG_EMAIL_SENT',                 'Your comments have been sent. Thank you.');
?>
