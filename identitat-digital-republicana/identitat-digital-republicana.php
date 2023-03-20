<?php
/*
Plugin Name: Identitat Digital Republicana
Plugin URI: https://siriondev.com
description: Integració amb el procés de validació de la Identitat Digital Republicana del Consell de la República Catalana
Version: 1.2.0
Author: Sirion Developers
Author URI: https://siriondev.com
License: GPL-3.0
*/

global $cxr_idr_plugin_version;
$cxr_idr_plugin_version = 1.2;

add_action('admin_menu', 'cxr_idr_setup_menu');

add_action('user_new_form', "cxr_idrepublicana_field" );
add_action('edit_user_profile', 'cxr_idrepublicana_field');
add_action('show_user_profile', 'cxr_idrepublicana_field');

add_action('user_register', 'save_cxr_idrepublicana');
add_action('profile_update', 'save_cxr_idrepublicana', 10, 3);

add_action('user_profile_update_errors', 'validate_cxr_idrepublicana', 0, 3);
add_filter('authenticate', 'authenticate_cxr_idrepublicana', 10000, 3);

add_filter('cxr_validate_idr', 'check_cxr_idrepublicana', 10, 2);
add_filter('cxr_validate_idr_detailed', 'check_cxr_idrepublicana_detailed', 10, 2);

add_action('plugins_loaded', 'cxr_idr_upgrade');

/**
 * Funció auxiliar per a actualitzar la base de dades
 *
 * @param void
 *
 * @return void
 */
function cxr_idr_upgrade()
{
    global $cxr_idr_plugin_version;

    $version = get_option("cxr_idr_version");

    if (!$version || $version < $cxr_idr_plugin_version) {

        if (get_option('cxr_idr', null) === null) {update_option('cxr_idr', 'true');}
        if (get_option('cxr_idr_inactive', null) === null) {update_option('cxr_idr_inactive', 'false');}
        if (get_option('cxr_idr_underaged', null) === null) {update_option('cxr_idr_underaged', 'false');}
        add_option("cxr_idr_version", $cxr_idr_plugin_version);
    }
}

/**
 * Afegeix la pàgina de gestió de la Identitat Digital Republicana
 *
 * @param void
 *
 * @return void
 */
function cxr_idr_setup_menu()
{
    add_menu_page('IDR', 'IDR', 'manage_options', 'identitat-digital-republicana', 'cxr_idr_page');
}

/**
 * Mostra la pàgina del plugin
 *
 * @param void
 *
 * @return void
 */
function cxr_idr_page()
{
    ?>

    <style>
        h1 {font-size: 23px;font-weight: 400;margin: 0;padding: 9px 0 4px;line-height: 1.3;margin-top: 10px;margin-bottom: 20px;}label {font-size: small;vertical-align: unset;}div.idr {margin-bottom: 20px;}div.subitems {margin-left: 20px;}input[type=checkbox]:disabled + label {color: #999;}
    </style>

    <?php
        if (isset($_POST['save'])) { 
            update_option('cxr_idr', isset($_POST['idr']) ? 'true' : 'false');
            update_option('cxr_idr_inactive', isset($_POST['inactive']) ? 'true' : 'false');
            update_option('cxr_idr_underaged', isset($_POST['underaged']) ? 'true' : 'false');
        }

        $idr = get_option('cxr_idr');
        $inactive = get_option('cxr_idr_inactive');
        $underaged = get_option('cxr_idr_underaged');
    ?>

    <h1>Configuració Identitat Digital Republicana</h1>
    <form method='post' action='' name='form' enctype='multipart/form-data'>

        <div class="idr">
            <input type='checkbox' name='idr' id="idr">
            <label for='idr'>Habilitar inici de sessió amb IDR</label><br>
        </div>

        <div class="subitems">
            <div class="idr">
                <input type='checkbox' name='inactive' id="inactive">
                <label for='idr'>Permetre IDR desactivades</label><br>
            </div>

            <div class="idr">
                <input type='checkbox' name='underaged' id="underaged">
                <label for='idr'>Permetre IDR de menors d'edat</label><br>
            </div>
        </div>

        <div class="idr">
            <input class="button" type='submit' name='save' value="Guardar configuració">
        </div>
    </form>

    <script>
        function options() {    
            if (document.getElementById("idr").checked) {
                document.getElementById("inactive").disabled = false;
                document.getElementById("underaged").disabled = false;
            } else {
                document.getElementById("inactive").disabled = true;
                document.getElementById("underaged").disabled = true;
            }
        }

        document.getElementById("idr").checked = <?php echo $idr ?>;
        document.getElementById("inactive").checked = <?php echo $inactive ?>;
        document.getElementById("underaged").checked = <?php echo $underaged ?>;
        
        options();
        document.getElementById("idr").addEventListener("click", options);
    </script>

    <?php
}

/**
 * Mostra el camp per a introduir la Identitat Digital Republicana a l'administrador
 *
 * @param WP_User $user
 *
 * @return void
 */
function cxr_idrepublicana_field(WP_User|string $user): void
{
    $meta = null;
    
    if ($user != 'add-new-user') {

        $meta = get_user_meta($user->id);
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

        $idr = apply_filters('cxr_validate_idr_detailed', $_POST['cxr_idrepublicana'], $user);

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
 * Comprova la validesa de la Identitat Digital Republicana contra la configuració del plugin
 *
 * @param string $idr
 * @param WP_User $user
 *
 * @return bool
 */
function check_cxr_idrepublicana(string $idr, \stdClass $user = null): bool
{
    $inactive = get_option('cxr_idr_inactive');

    $underaged = get_option('cxr_idr_underaged');
    
    $idr = apply_filters('cxr_validate_idr_detailed', $idr, $user);

    if ($inactive == 'false' && !$idr->active) {

        return false;
    }

    if ($underaged == 'false' && $idr->underaged) {

        return false;
    }

    if (!$idr->valid) {

        return false;
    }

    if ($idr->used) {

        return false;
    }

    return true;
}

/**
 * Comprova la validesa de la Identitat Digital Republicana contra el servei de validació de la Consell de la República
 *
 * @param string $idr
 * @param WP_User $user
 *
 * @return stdClass $status
 */
function check_cxr_idrepublicana_detailed(string $idr, \stdClass $user = null): \stdClass
{
    $status = new \stdClass();

    $status->value = $idr;

    $status->format = false;

    $status->valid = false;
        
    $status->active = false;

    $status->underaged = false;

    $status->used = false;

    if (preg_match("/[A-Za-z]{1}\-[0-9]{3}\-[0-9]{5}/", $idr)) {

        $status->format = true;

        $response = wp_remote_get('https://apis.consellrepublica.cat/idserv/validate?idCiutada=' . $idr);

        $json = json_decode($response['body'], true);

        if (isset($json['state'])) {

            if ($json['state'] == 'VALID_ACTIVE') {

                $status->active = true;

                $status->valid = true;
            }

            if ($json['state'] == 'VALID_INACTIVE') {

                $status->valid = true;
            }

            if ($json['state'] == 'VALID_UNDERAGED') {

                $status->active = true;

                $status->valid = true;

                $status->underaged = true;
            }
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
function authenticate_cxr_idrepublicana(WP_User|WP_Error|null $user, string $username, string $password): WP_User|WP_Error
{
    if (get_option('cxr_idr') == 'true') {

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
    }

    return $user;
}

?>
