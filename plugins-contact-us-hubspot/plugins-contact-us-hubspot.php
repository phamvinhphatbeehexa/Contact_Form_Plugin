<?php
/*
Plugin Name: Contact Form Plugin
Description: This plugin captures user data from the Contact Us page and sends it to a HubSpot form.
Version: 1.0
*/

class Contact_Fields_Plugin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'plugin_settings_Contact_Form_Plugin_page' ) );
    }

    public function plugin_settings_Contact_Form_Plugin_page() {
        $page_title = 'Contact Form Plugin';
        $menu_title = 'Contact Form Plugin';
        $capability = 'manage_options';
        $slug = 'smashing_fields';
        $callback = array( $this, 'plugin_settings_page_content' );
        $icon = 'dashicons-admin-plugins';
        $position = 100;

        add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }

    public function plugin_settings_page_content() {
        $api_key_hubspot = get_option( 'api_key_hubspot' );
        $form_id_hubspot = get_option( 'form_id_hubspot' );
        $authorization = get_option( 'authorization_hubspot' );
        $new_form_list_contact = get_option( 'form_list_contact' );

        if ( isset( $_POST['submit'] ) ) {
            update_option( 'api_key_hubspot', $_POST['api_key_hubspot'] );
            update_option( 'form_id_hubspot', $_POST['form_id_hubspot'] );
            update_option( 'authorization_hubspot', $_POST['authorization_hubspot'] );
            update_option( 'form_list_contact', $_POST['form_list_contact'] );

            $new_form_list_contact = $_POST['form_list_contact'];

            if ( !empty( $new_form_list_contact ) ) {
                for ( $index = 0; $index < count($new_form_list_contact); $index++ ) {
                    $input_id = 'form_list_contact_' . $index;
                    update_option( $input_id, $new_form_list_contact[$index] );
                }
            } else {
                update_option( 'form_list_contact', '' );
            }

            $api_key_hubspot = get_option( 'api_key_hubspot' );
            $form_id_hubspot = get_option( 'form_id_hubspot' );
            $authorization = get_option( 'authorization_hubspot' );
            $new_form_list_contact = get_option( 'form_list_contact' );
        }

        $cf7_forms = get_posts( array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1
        ) );

        $all_id_forms_hubspot = is_array($form_id_hubspot) ? $form_id_hubspot : array();

        $form_options = array();

        foreach ( $cf7_forms as $cf7_form ) {
            $form_options[ $cf7_form->ID ] = $cf7_form->post_title;
        }

        ?>
        <div class="wrap">
            <h2>Settings Contact Form Plugin</h2>
            <form method="POST">
                <table class="form-table">
                    <tbody>

                    <tr>
                        <th><label for="api_key_hubspot">API Key Hubspot: </label></th>
                        <td><input name="api_key_hubspot" id="api_key_hubspot" type="text" value="<?php echo esc_attr( $api_key_hubspot ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="authorization_hubspot">Authorization Hubspot: </label></th>
                        <td><input name="authorization_hubspot" id="authorization_hubspot" type="text" value="<?php echo esc_attr( $authorization ); ?>" class="regular-text" /></td>
                    </tr>

                    <?php
                    $total_rows = max(count($all_id_forms_hubspot), 1);
                        for ( $index = 0; $index < $total_rows; $index++ ) {
                            ?>
                            <tr class="form-row">
                                <th><label for="form_list_contact_<?php echo $index; ?>">Contact Us Form: </label></th>
                                <td style="width: 100px;">
                                    <select name="form_list_contact[]" class="form-list-contact" id="form_list_contact_<?php echo $index; ?>">
                                        <?php if ( !empty( $new_form_list_contact ) ) { ?>
                                            <option value="" selected>Please select a contact form</option>
                                            <?php foreach ( $form_options as $form_id => $form_name ) { ?>
                                                <option value="<?php echo esc_attr( $form_id ); ?>" <?php echo selected( $new_form_list_contact[$index], $form_id ); ?>><?php echo esc_html( $form_name ); ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                </td>
                                <th style="width: 120px;"><label for="form_id_hubspot_<?php echo $index; ?>">Form ID Hubspot: </label></th>
                                <td style="width: 100px;"><input style="width: 300px;" name="form_id_hubspot[]" id="form_id_hubspot_<?php echo $index; ?>" type="text" value="<?php echo isset($form_id_hubspot[$index]) ? esc_attr( $form_id_hubspot[$index] ) : ''; ?>" class="regular-text" /></td>
                                <td class="action" style="padding-left: 50px;">
                                    <button type="button" class="delete-row" data-index="<?php echo $index; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php } ?>

                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Setting">
                    <button type="button" id="add-row" class="button">Add Row</button>
                </p>
            </form>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#add-row').click(function() {
                    var cloneRow = $('.form-row:last').clone();
                    cloneRow.find('select').val('');
                    cloneRow.find('input').val('');
                    cloneRow.insertAfter('.form-row:last');
                });
                $(document).on('click', '.delete-row', function() {
                    var index = $(this).data('index');
                    $('.form-row:eq(' + index + ')').remove();
                });
            });
        </script>
        <?php

    }
}

new Contact_Fields_Plugin();

add_action('wpcf7_mail_sent', 'my_custom_mail_sent' );

function my_custom_mail_sent( $contact_form ) {
    $submission = WPCF7_Submission::get_instance();
    if ( $submission ) {

        $form_id = $contact_form->id();

        $api_key_hubspot = get_option( 'api_key_hubspot' );
        $list_form_id_hubspot = get_option( 'form_id_hubspot' );
        $authorization = get_option( 'authorization_hubspot' );
        $new_form_list_contact = get_option( 'form_list_contact' );

        $count_form_list_contact = count($new_form_list_contact);


        for ($i = 0; $i < $count_form_list_contact; $i++) {

            if ($new_form_list_contact[$i] == $form_id) {
                $form_id_hubspot = $list_form_id_hubspot[$i];
                $posted_data = $submission->get_posted_data();

                //Fill in the correct name so can get data from the form
                $email = $posted_data['email'];
                $FirstName = $posted_data['firssstname'];
                $LastName = $posted_data['lastname'];
                $PhoneNumber = $posted_data['phone'];
                $Subject = $posted_data['subject'];
                $message = $posted_data['content'];

                $hubspot_data = array(
                    'fields' => array(
                        array(
                            "name" => "lastname",
                            "value" => $LastName
                        ),
                        array(
                            "name" => "firstname",
                            "value" => $FirstName
                        ),
                        array(
                            'name' => 'email',
                            'value' => $email
                        ),
                        array(
                            "name" => "phone",
                            "value" => $PhoneNumber
                        ),
                        array(
                            "name" => "subject",
                            "value" => $Subject
                        ),
                        array(
                            'name' => 'message',
                            'value' => $message
                        )
                    )
                );
                $ch = curl_init();

                curl_setopt( $ch, CURLOPT_URL, "https://api.hsforms.com/submissions/v3/integration/secure/submit/" . $api_key_hubspot . "/" . $form_id_hubspot);
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_HEADER, false );
                curl_setopt( $ch, CURLOPT_POST, true );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $hubspot_data ) );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/json", 'Authorization: Bearer ' . $authorization) );

                curl_exec( $ch );
                curl_close( $ch );
            }
        }
    }
}



