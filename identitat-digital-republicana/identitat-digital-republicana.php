<?php
/*
Plugin Name: Identitat Digital Republicana
Plugin URI: https://siriondev.com
description: Integració amb el procés de validació de la Identitat Digital Republicana del Consell de la República Catalana
Version: 1.0.3
Author: Sirion Developers
Author URI: https://siriondev.com
License: GPL-3.0
*/

add_action('user_new_form', "cxr_idrepublicana_field" );
add_action('edit_user_profile', 'cxr_idrepublicana_field');
add_action('show_user_profile', 'cxr_idrepublicana_field');

add_action('user_register', 'save_cxr_idrepublicana');
add_action('profile_update', 'save_cxr_idrepublicana', 10, 3);

add_action('user_profile_update_errors', 'validate_cxr_idrepublicana', 0, 3);
add_filter('authenticate', 'authenticate_cxr_idrepublicana', 100, 3);

add_filter('cxr_validate_idr', 'check_cxr_idrepublicana', 10, 2);


/**
 * Mostra el camp per a introduir la Identitat Digital Republicana a l'administrador
 *
 * @param WP_User $user
 *
 * @return void
 */
function cxr_idrepublicana_field(WP_User $user): void
{
    $meta = null;
    
    if ($user != 'add-new-user') {

        $meta = $users = get_user_meta($user->id);
    }

    $cxr_idrepublicana = isset($meta['cxr_idrepublicana'][0]) ? $meta['cxr_idrepublicana'][0] : '';

    $cxr_idrepublicana = preg_replace('/[^a-z0-9\-]/i', '_', $cxr_idrepublicana); ?>

    <h3 class="heading">Consell de la República</h3>

    <table class="form-table">

        <tr>

            <th><label for="contact">Identitat Digital Republicana</label></th>

            <td><input type="text" class="input-text form-control" name="cxr_idrepublicana" id="cxr_idrepublicana" placeholder="C-999-99999" value="<?php echo $cxr_idrepublicana; ?>"/></td>

        </tr>

    </table> <?php
}

/**
 * Guarda el valor de la Identitat Digital Republicana
 *
 * @param int $user_id
 * @param WP_User $old_user_data
 * @param array $new_user_data
 *
 * @return void
 */
function save_cxr_idrepublicana(int $user_id, WP_User $old_user_data = null, array $new_user_data = null): void
{
    if (current_user_can('edit_user', $user_id)) {

        $idr = preg_replace('/[^a-z0-9\-]/i', '_', $_POST['cxr_idrepublicana']);

        update_user_meta($user_id, 'cxr_idrepublicana', $idr);
    }
}

/**
 * Retorna l'estat de la Identitat Digital Republicana
 *
 * @param WP_Error $errors
 * @param bool $update
 * @param WP_User $user
 *
 * @return WP_Error $errors
 */
function validate_cxr_idrepublicana(WP_Error $errors, bool $update, \stdClass $user): WP_Error
{
    if (!empty($_POST['cxr_idrepublicana'])) {

        $idr = apply_filters('cxr_validate_idr', $_POST['cxr_idrepublicana'], $user);

        if (!$idr->valid) {

            $errors->add('cxr_idrepublicana', "<strong>Error</strong>: L'ID Republicana introduïda no és vàlida.");
        }

        if ($idr->used) {

            $errors->add('cxr_idrepublicana', "<strong>Error</strong>: L'ID Republicana introduïda ja està en ús.");
        }
    }

    return $errors;
}

/**
 * Comprova la validesa de la Identitat Digital Republicana
 *
 * @param string $idr
 * @param WP_User $user
 *
 * @return stdClass $status
 */
function check_cxr_idrepublicana(string $idr, \stdClass $user = null): \stdClass
{
    $status = new \stdClass();

    $status->value = $idr;

    $status->valid = false;

    if (preg_match("/[A-Za-z]{1}\-[0-9]{3}\-[0-9]{5}/", $idr)) {

        $status->valid = true;

        $response = wp_remote_get('https://apis.consellrepublica.cat/idserv/validate?idCiutada=' . $idr);

        $json = json_decode($response['body'], true);

        if (isset($json['state']) && $json['state'] == 'VALID_ACTIVE') {

            $status->valid = true;
        }

        $args = array(
            'meta_key'     => 'cxr_idrepublicana',
            'meta_value'   => $idr,
            'meta_compare' => '=',
        );

        if (isset($user->ID)) {

            $args['exclude'] = array($user->ID);
        }

        $user_query = new WP_User_Query($args);

        $users = $user_query->get_results();

        $status->used = $users ? true : false; 
    }

    return $status;
}

/**
 * Utilitza la Identitat Digital Republicana per a iniciar sessió
 *
 * @param WP_User|WP_Error $user
 * @param string $username
 * @param string $password
 *
 * @return WP_User|WP_Error
 */

function authenticate_cxr_idrepublicana(WP_User|WP_Error $user, string $username, string $password): WP_User|WP_Error
{
    if (is_a($user, 'WP_User')) {

        return $user;
    }

    $args = array(
        'meta_key'     => 'cxr_idrepublicana',
        'meta_value'   => $username,
        'meta_compare' => '=',
    );

    $user_query = new WP_User_Query($args);

    $query_users = $user_query->get_results();

    if (sizeof($query_users) == 1) {
        
        $check = wp_check_password($password, $query_users[0]->user_pass, $query_users[0]->ID);

        if ($check) {

            $user = $query_users[0];
        }
    }

    return $user;
}

?>
