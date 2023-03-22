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
    add_menu_page('IDR', 'IDR', 'manage_options', 'identitat-digital-republicana', 'cxr_idr_page', 'data:image/svg+xml;base64,PHN2ZyBpZD0ic3ZnIiB2ZXJzaW9uPSIxLjEiIHdpZHRoPSI0MDAiIGhlaWdodD0iMzk4LjQzNzUiIHZpZXdCb3g9IjAsIDAsIDQwMCwzOTguNDM3NSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8ZGVmcz48L2RlZnM+CiAgPGcgaWQ9InN2Z2ciPgogICAgPHBhdGggaWQ9InBhdGgwIiBkPSJNIDM2Ljg3MiA1Ni43NjggTCAzNi44NzIgNzYuOTcgTCAxMTguMTk3IDc2Ljk3IEwgMTk5LjUyMiA3Ni45NyBMIDE5OS41MjIgOTcuMTcyIEwgMTk5LjUyMiAxMTcuMzc0IEwgMjgxLjM2NSAxMTcuMzc0IEwgMzYzLjIwOCAxMTcuMzc0IEwgMzYzLjIwOCA5Ny4xNzQgTCAzNjMuMjA4IDc2Ljk3NCBMIDI4MS43NTQgNzYuODQyIEwgMjAwLjI5OSA3Ni43MTEgTCAyMDAuMTYzIDU2LjYzOCBMIDIwMC4wMjggMzYuNTY3IEwgMTE4LjQ1IDM2LjU2NyBMIDM2Ljg3MiAzNi41NjcgTCAzNi44NzIgNTYuNzY4IE0gMzYuODcyIDEzOC42MTIgTCAzNi44NzIgMTU4LjgxMyBMIDExOC4xOTcgMTU4LjgxMyBMIDE5OS41MjIgMTU4LjgxMyBMIDE5OS41MjIgMTc5LjAxNCBMIDE5OS41MjIgMTk5LjIxNyBMIDI4MS4zNjUgMTk5LjIxNyBMIDM2My4yMDggMTk5LjIxNyBMIDM2My4yMDggMTc5LjAxNyBMIDM2My4yMDggMTU4LjgxNiBMIDI4MS43NTQgMTU4LjY4NiBMIDIwMC4yOTkgMTU4LjU1NSBMIDIwMC4xNjMgMTM4LjQ4MiBMIDIwMC4wMjggMTE4LjQwOSBMIDExOC40NSAxMTguNDA5IEwgMzYuODcyIDExOC40MDkgTCAzNi44NzIgMTM4LjYxMiBNIDM2Ljc1NCAyMjAuOTcyIEwgMzYuNjEzIDI0MS4xNzUgTCAxMTguMzI3IDI0MS4xNzUgTCAyMDAuMDQgMjQxLjE3NCBMIDIwMC4wNCAyMjAuOTcyIEwgMjAwLjA0IDIwMC43NzEgTCAxMTguNDY3IDIwMC43NzEgTCAzNi44OTYgMjAwLjc3MSBMIDM2Ljc1NCAyMjAuOTcyIE0gMTk5LjUyMiAyNjIuNDEyIEwgMTk5LjUyMiAyODIuNjEzIEwgMTE4LjE5NyAyODIuNjEzIEwgMzYuODcyIDI4Mi42MTMgTCAzNi44NzIgMzAyLjgxNCBMIDM2Ljg3MiAzMjMuMDE0IEwgMTE4LjA2OCAzMjMuMTQ2IEwgMTk5LjI2MyAzMjMuMjc2IEwgMTk5LjM5OCAzNDIuNTY5IEwgMTk5LjUzNSAzNjEuODYgTCAyODEuMzcyIDM2MS45OTMgTCAzNjMuMjA4IDM2Mi4xMjUgTCAzNjMuMjA4IDM0Mi4wNTUgTCAzNjMuMjA4IDMyMS45ODQgTCAyODEuNzU0IDMyMS44NTMgTCAyMDAuMjk5IDMyMS43MjMgTCAyMDAuMTYzIDMwMi4xNjcgTCAyMDAuMDI4IDI4Mi42MTMgTCAyODEuNjE4IDI4Mi42MTMgTCAzNjMuMjA4IDI4Mi42MTMgTCAzNjMuMjA4IDI2Mi40MTIgTCAzNjMuMjA4IDI0Mi4yMTEgTCAyODEuMzY1IDI0Mi4yMTEgTCAxOTkuNTIyIDI0Mi4yMTEgTCAxOTkuNTIyIDI2Mi40MTIiIHN0cm9rZT0ibm9uZSIgZmlsbD0iI2ZjMWMxYyIgZmlsbC1ydWxlPSJldmVub2RkIiBzdHlsZT0iIj48L3BhdGg+CiAgICA8cGF0aCBpZD0icGF0aDIiIGQ9Ik0gNzcuNDQ1IDc3LjM1OCBDIDk5LjkwNCA3Ny40MzQgMTM2LjUgNzcuNDM0IDE1OC43NyA3Ny4zNTggQyAxODEuMDQyIDc3LjI4NCAxNjIuNjY2IDc3LjIyMyAxMTcuOTM4IDc3LjIyMyBDIDczLjIwOSA3Ny4yMjMgNTQuOTg3IDc3LjI4NCA3Ny40NDUgNzcuMzU4IE0gMzYzLjMzMiA5Ni45NzcgTCAzNjMuMjA4IDExNy4yNDQgTCAxOTkuNjUyIDExNy43NjIgTCAzNi4wOTUgMTE4LjI4MiBMIDE5OS41MjIgMTE4LjAzNSBDIDI4OS40MDggMTE3LjkgMzYzLjEyNiAxMTcuNjIzIDM2My4zNDQgMTE3LjQyIEMgMzYzLjU2MSAxMTcuMjE3IDM2My42NzUgMTA3Ljk3NCAzNjMuNTk3IDk2Ljg4MSBMIDM2My40NTUgNzYuNzExIEwgMzYzLjMzMiA5Ni45NzcgTSAyNDEuMTMyIDE1OC42ODMgQyAyNjMuNTkgMTU4Ljc1OSAzMDAuMTg2IDE1OC43NTkgMzIyLjQ1NyAxNTguNjgzIEMgMzQ0LjcyOCAxNTguNjA5IDMyNi4zNTMgMTU4LjU0OCAyODEuNjI0IDE1OC41NDggQyAyMzYuODk1IDE1OC41NDggMjE4LjY3NCAxNTguNjA5IDI0MS4xMzIgMTU4LjY4MyBNIDM2My40NDQgMTc5LjAxNCBDIDM2My40NDQgMTkwLjI2OSAzNjMuNTEyIDE5NC44NzEgMzYzLjU5NiAxODkuMjQ1IEMgMzYzLjY3OSAxODMuNjE4IDM2My42NzkgMTc0LjQxMiAzNjMuNTk2IDE2OC43ODUgQyAzNjMuNTEyIDE2My4xNTcgMzYzLjQ0NCAxNjcuNzYxIDM2My40NDQgMTc5LjAxNCBNIDc3LjE0NiAyNDEuNTYyIEMgOTkuNzI1IDI0MS42MzggMTM2LjY3IDI0MS42MzggMTU5LjI0OCAyNDEuNTYyIEMgMTgxLjgyNyAyNDEuNDg4IDE2My4zNTMgMjQxLjQyNyAxMTguMTk3IDI0MS40MjcgQyA3My4wNDIgMjQxLjQyNyA1NC41NjggMjQxLjQ4OCA3Ny4xNDYgMjQxLjU2MiBNIDM2My40NDMgMjYyLjY3IEMgMzYzLjQ0NCAyNzMuNzgyIDM2My41MTIgMjc4LjI1MyAzNjMuNTk2IDI3Mi42MDggQyAzNjMuNjgxIDI2Ni45NjMgMzYzLjY3OSAyNTcuODcxIDM2My41OTYgMjUyLjQwNyBDIDM2My41MTIgMjQ2Ljk0MSAzNjMuNDQzIDI1MS41NTkgMzYzLjQ0MyAyNjIuNjcgTSAyODIuMDA3IDMyMS44NTEgTCAzNjMuMTk2IDMyMS45ODQgTCAzNjMuMzQxIDM0Mi4wNTUgTCAzNjMuNDg2IDM2Mi4xMjUgTCAzNjMuNDc2IDM0MS45MjQgTCAzNjMuNDY3IDMyMS43MjMgTCAyODIuMTQyIDMyMS43MiBMIDIwMC44MTcgMzIxLjcxNyBMIDI4Mi4wMDcgMzIxLjg1MSIgc3Ryb2tlPSJub25lIiBmaWxsPSIjZmM5OTk5IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIHN0eWxlPSIiPjwvcGF0aD4KICAgIDxwYXRoIGlkPSJwYXRoMyIgZD0iTSAzNi41OTEgMzYuNjk2IEMgMzYuNDU0IDM3LjA1NCAzNi40MDUgNDYuMzIgMzYuNDgzIDU3LjI4OCBMIDM2LjYyNSA3Ny4yMyBMIDM2Ljc0OSA1Ni44OTggTCAzNi44NzIgMzYuNTY3IEwgMTE4LjQ1IDM2LjU2NyBMIDIwMC4wMjggMzYuNTY3IEwgMjAwLjE2MyA1Ni42MzggTCAyMDAuMjk5IDc2LjcxMSBMIDI4MS44ODQgNzYuNzE0IEwgMzYzLjQ2NyA3Ni43MTYgTCAyODIuMDE5IDc2LjU4MiBMIDIwMC41NzEgNzYuNDQ5IEwgMjAwLjQzNSA1Ni4zNzkgTCAyMDAuMjk5IDM2LjMwNyBMIDExOC41NjkgMzYuMTc2IEMgNTMuMzY0IDM2LjA3MSAzNi43OSAzNi4xNzcgMzYuNTkxIDM2LjY5NiBNIDM2LjYwNCAxMzguNzQgTCAzNi42MTMgMTU5LjA3MiBMIDExNy45MzggMTU5LjA3NCBMIDE5OS4yNjMgMTU5LjA3NyBMIDExOC4wNzMgMTU4Ljk0MyBMIDM2Ljg4NCAxNTguODEgTCAzNi43MzkgMTM4LjYwOSBMIDM2LjU5NSAxMTguNDA5IEwgMzYuNjA0IDEzOC43NCBNIDIwMC4yNzYgMTM4LjM1MiBDIDIwMC4yNzYgMTQ5LjQ2MyAyMDAuMzQ0IDE1My45MzQgMjAwLjQyOSAxNDguMjg5IEMgMjAwLjUxMiAxNDIuNjQ0IDIwMC41MTIgMTMzLjU1MyAyMDAuNDI3IDEyOC4wODggQyAyMDAuMzQ0IDEyMi42MjIgMjAwLjI3NiAxMjcuMjQxIDIwMC4yNzYgMTM4LjM1MiBNIDI0MC4zMTQgMTk5LjYwNSBDIDI2Mi44OTIgMTk5LjY4IDI5OS44MzggMTk5LjY4IDMyMi40MTYgMTk5LjYwNSBDIDM0NC45OTUgMTk5LjUzIDMyNi41MjIgMTk5LjQ2OSAyODEuMzY1IDE5OS40NjkgQyAyMzYuMjA5IDE5OS40NjkgMjE3LjczNiAxOTkuNTMgMjQwLjMxNCAxOTkuNjA1IE0gMzYuNTkxIDIwMC45IEMgMzYuNDU0IDIwMS4yNTggMzYuNDA1IDIxMC40MDggMzYuNDgzIDIyMS4yMzQgTCAzNi42MjUgMjQwLjkxNSBMIDM2Ljc0OSAyMjAuODQyIEwgMzYuODcyIDIwMC43NzEgTCAxMTguNDUgMjAwLjc3MSBMIDIwMC4wMjggMjAwLjc3MSBMIDIwMC4xNzMgMjIwLjk3MiBMIDIwMC4zMTcgMjQxLjE3NSBMIDIwMC4zMDggMjIwLjg0MiBMIDIwMC4yOTkgMjAwLjUxMSBMIDExOC41NjkgMjAwLjM4IEMgNTMuMzUxIDIwMC4yNzYgMzYuNzkgMjAwLjM4MSAzNi41OTEgMjAwLjkgTSAzNi40NzcgMzAyLjk0NCBMIDM2LjM1NSAzMjMuNTM2IEwgMTE3LjgwOCAzMjMuNDA3IEwgMTk5LjI2MyAzMjMuMjggTCAxMTguMDczIDMyMy4xNDcgTCAzNi44ODQgMzIzLjAxNCBMIDM2Ljc0MiAzMDIuNjg0IEwgMzYuNiAyODIuMzU1IEwgMzYuNDc3IDMwMi45NDQgTSAyMDAuMjc1IDMwMi4wMzkgQyAyMDAuMjc1IDMxMi44NjUgMjAwLjM0NCAzMTcuMzY3IDIwMC40MjcgMzEyLjA0NCBDIDIwMC41MTIgMzA2LjcyMSAyMDAuNTEyIDI5Ny44NjMgMjAwLjQyOSAyOTIuMzYgQyAyMDAuMzQ0IDI4Ni44NTcgMjAwLjI3NSAyOTEuMjEzIDIwMC4yNzUgMzAyLjAzOSBNIDI0MC4zNTUgMzYyLjI1NSBDIDI2Mi45NTUgMzYyLjMzIDI5OS43ODUgMzYyLjMzIDMyMi4xOTkgMzYyLjI1NSBDIDM0NC42MTIgMzYyLjE4MSAzMjYuMTIgMzYyLjEyIDI4MS4xMDYgMzYyLjEyIEMgMjM2LjA5MyAzNjIuMTIgMjE3Ljc1NSAzNjIuMTgxIDI0MC4zNTUgMzYyLjI1NSIgc3Ryb2tlPSJub25lIiBmaWxsPSIjZmM3NDc0IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIHN0eWxlPSIiPjwvcGF0aD4KICAgIDxwYXRoIGlkPSJwYXRoNCIgZD0iTSAxOTkuMjM5IDk3LjQzMSBDIDE5OS4yMzkgMTA4LjU0MiAxOTkuMzA4IDExMy4wMTMgMTk5LjM5MiAxMDcuMzY4IEMgMTk5LjQ3NSAxMDEuNzIyIDE5OS40NzUgOTIuNjMyIDE5OS4zOTIgODcuMTY2IEMgMTk5LjMwOCA4MS43MDEgMTk5LjIzOSA4Ni4zMiAxOTkuMjM5IDk3LjQzMSBNIDE5OS4yMzkgMTc5LjI3NCBDIDE5OS4yMzkgMTkwLjM4NSAxOTkuMzA4IDE5NC44NTcgMTk5LjM5MiAxODkuMjEgQyAxOTkuNDc1IDE4My41NjUgMTk5LjQ3NSAxNzQuNDc0IDE5OS4zOTIgMTY5LjAwOSBDIDE5OS4zMDggMTYzLjU0MyAxOTkuMjM5IDE2OC4xNjMgMTk5LjIzOSAxNzkuMjc0IE0gMTk5LjEyOCAyNjIuMDIyIEwgMTk5LjAwNSAyODIuMDkyIEwgMTE3LjU1IDI4Mi4yMjUgTCAzNi4wOTUgMjgyLjM1OSBMIDExNy41NjYgMjgyLjQ4NiBDIDE4Mi40NjggMjgyLjU4OCAxOTkuMDg3IDI4Mi40ODIgMTk5LjI4NiAyODEuOTY1IEMgMTk5LjQyMiAyODEuNjA4IDE5OS40NzEgMjcyLjQ1OCAxOTkuMzkzIDI2MS42MzMgTCAxOTkuMjUgMjQxLjk1MSBMIDE5OS4xMjggMjYyLjAyMiBNIDI0MS4wOTEgMjgzLjAwMiBDIDI2My41MjcgMjgzLjA3NyAzMDAuMjQgMjgzLjA3NyAzMjIuNjc1IDI4My4wMDIgQyAzNDUuMTEgMjgyLjkyNyAzMjYuNzU1IDI4Mi44NjYgMjgxLjg4NCAyODIuODY2IEMgMjM3LjAxMiAyODIuODY2IDIxOC42NTUgMjgyLjkyNyAyNDEuMDkxIDI4My4wMDIgTSAxOTkuMjM5IDM0Mi45NiBDIDE5OS4yMzkgMzUzLjc4NiAxOTkuMzA4IDM1OC4yODkgMTk5LjM5MiAzNTIuOTY1IEMgMTk5LjQ3NyAzNDcuNjQyIDE5OS40NzcgMzM4Ljc4NSAxOTkuMzkyIDMzMy4yODIgQyAxOTkuMzA4IDMyNy43NzkgMTk5LjIzOSAzMzIuMTM0IDE5OS4yMzkgMzQyLjk2IiBzdHJva2U9Im5vbmUiIGZpbGw9IiNmY2M5YzkiIGZpbGwtcnVsZT0iZXZlbm9kZCIgc3R5bGU9IiI+PC9wYXRoPgogIDwvZz4KPC9zdmc+Cg==');
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
    if (isset($_POST['cxr_idrepublicana'])) {

        if (current_user_can('edit_user', $user_id)) {

            $idr = preg_replace('/[^a-z0-9\-]/i', '_', $_POST['cxr_idrepublicana']);
    
            update_user_meta($user_id, 'cxr_idrepublicana', $idr);
        }
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
