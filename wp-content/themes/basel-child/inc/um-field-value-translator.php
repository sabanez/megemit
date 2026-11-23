<?php
/**
 * Traduce los values de campos radio/checkbox de Ultimate Member
 * para que el valor enviado coincida con los internal values de HubSpot,
 * manteniendo el label visible intacto.
 *
 * Para añadir más campos: añadir entrada en $mgmit_um_field_maps.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$mgmit_um_field_maps = array(
    'contact_job_title_es' => array(
        'type' => 'radio',
        'map'  => array(
            'Sr.'  => 'mr',
            'Sra.' => 'mrs',
            'Dr.'  => 'dr',
        ),
    ),
    'contact_especialidad_principal' => array(
        'type' => 'checkbox',
        'map'  => array(
            'Allergology'                        => 'Alergología',
            'Biology/Biochemistry/Biotechnology' => 'Biología/bioquímica/biotecnología',
            'Cardiology'                         => 'Cardiología',
            'Dentistry'                          => 'Odontología',
            'Dermatology'                        => 'Dermatología',
            'Endocrinology'                      => 'Endocrinología',
            'Gastroenterology'                   => 'Gastroenterología',
            'General Medicine'                   => 'Medicina general',
            'Geriatrics'                         => 'Geriatría',
            'Gynaecology and Obstetrics'         => 'Ginecología y obstetricia',
            'Immunology'                         => 'Inmunología',
            'Midwife'                            => 'Matrona',
            'Neurology'                          => 'Neurología',
            'Nurse'                              => 'Enfermería',
            'Nutritionist'                       => 'Nutricionista',
            'Oncology'                           => 'Oncología',
            'Ophthalmology'                      => 'Oftalmología',
            'Orthopaedics'                       => 'Traumatología y ortopedia',
            'Other'                              => 'Otro',
            'Otorhinolaryngology'                => 'Otorrinolaringología',
            'Paediatrics'                        => 'Pediatría',
            'Physiotherapist'                    => 'Fisioterapia',
            'Pneumology'                         => 'Neumología',
            'Psychiatry'                         => 'Psiquiatría',
            'Psychologist'                       => 'Psicología',
            'Rheumatology'                       => 'Reumatología',
            'Sports medicine'                    => 'Medicina deportiva',
            'Student/Investigator'               => 'Estudiante/investigador',
            'Surgery'                            => 'Cirugía',
            'Traumatology'                       => 'Traumatología',
            'Urology'                            => 'Urología',
            'Veterinary'                         => 'Veterinaria',
        ),
    ),
);

foreach ( $mgmit_um_field_maps as $field_key => $config ) {
    $map  = $config['map'];
    $type = $config['type'];

    if ( 'radio' === $type ) {

        add_filter( 'um_radio_options_pair__' . $field_key, '__return_true' );

        add_filter( 'um_radio_field_options_' . $field_key, function ( $options ) use ( $map ) {
            $new_options = array();
            foreach ( $options as $k => $v ) {
                $hs_value                = isset( $map[ $v ] ) ? $map[ $v ] : $v;
                $new_options[ $hs_value ] = $v;
            }
            return $new_options;
        }, 10, 1 );

    } elseif ( 'checkbox' === $type ) {

        $reverse_map = array();
        foreach ( $map as $label => $hs_value ) {
            if ( ! isset( $reverse_map[ $hs_value ] ) ) {
                $reverse_map[ $hs_value ] = $label;
            }
        }

        add_filter( 'um_checkbox_field_options_' . $field_key, function ( $options ) use ( $map ) {
            $new_options = array();
            foreach ( $options as $k => $v ) {
                $new_v             = isset( $map[ $v ] ) ? $map[ $v ] : $v;
                $new_options[ $k ] = $new_v;
            }
            return $new_options;
        }, 10, 1 );

        add_filter( 'um_field_checkbox_item_title', function ( $title, $key, $v, $data ) use ( $field_key, $reverse_map ) {
            if ( $key !== $field_key ) {
                return $title;
            }
            return isset( $reverse_map[ $v ] ) ? $reverse_map[ $v ] : $title;
        }, 10, 4 );
    }
}
