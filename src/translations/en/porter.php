<?php
/**
 * Porter plugin for Craft CMS 3.x
 *
 * A toolkit with lots of helpers for users and accounts
 *
 * @link      https://bymayo.co.uk
 * @copyright Copyright (c) 2020 Jason Mayo
 */

/**
 * @author    Jason Mayo
 * @package   Porter
 * @since     1.0.0
 */
return [
    'Porter plugin loaded' => 'Porter plugin loaded',
    'porter_delete_account_flash_success' => 'Your account has been deleted.',
    'porter_delete_account_flash_admins' => 'Admins cannot delete their accounts.',
    'porter_delete_account_flash_incorrect' => 'You didn\'t type the phrase correctly.',
    'porter_delete_account_flash_permission' => 'You don\'t have permission to delete your account.',
    'porter_delete_account_confirmation_email_heading' => 'When a user has deleted their account.',
    'porter_delete_account_confirmation_email_subject' => 'Your account has been deleted',
    'porter_delete_account_confirmation_email_body' => 'Hey {{ user.friendlyName }},
    
    Your account has been successfully deleted. 

    If you weren’t expecting this email, and your account has been deleted by mistake please contact us.',
    'porter_deactivate_account_flash_success' => 'Your account has been deactivated.',
    'porter_deactivate_account_flash_admins' => 'Admins cannot deactivate their accounts.',
    'porter_deactivate_account_flash_permission' => 'You don\'t have permission to deactivate your account.',
    'porter_deactivate_account_confirmation_email_heading' => 'When a user has deactivated their account.',
    'porter_deactivate_account_confirmation_email_subject' => 'Your account has been deactivated',
    'porter_deactivate_account_confirmation_email_body' => 'Hey {{ user.friendlyName }},
    
    Your account has been successfully deactivated. 

    If you weren’t expecting this email, and your account has been deactivated by mistake please contact us.',
    'porter_magic_link_token_expired' => 'Magic link token has expired.',
    'porter_magic_link_sent' => 'A magic link has been sent, if the email address exists as a user',
    'porter_magic_link_email_heading' => 'When a user requests a magic link to login.',
    'porter_magic_link_email_subject' => 'Your magic link request',
    'porter_magic_link_email_body' => 'Hey {{ user.friendlyName }},
    
    You asked us to send you a magic link so you can quickly sign in securely. 

    {{link}}

    The link above is a magic link, only meant for you. Please don\'t share it with anyone.

    If you weren’t expecting this email, just ignore it.'
];
