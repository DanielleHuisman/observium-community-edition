<?php

$links['this']  = generate_url($vars);
$links['bills'] = generate_url(['page' => 'bills']);

    $form = ['type' => 'horizontal',
        'userlevel' => 10,          // Minimum user level for display form
        'id'        => 'modal-add_bill',
        'title'     =>  'Add Bill',
        'url'  => generate_url(['page' => 'bills']),
    ];

    /*
    // top row div
    $form['fieldset']['bill'] = ['div'   => 'top',
        'title' => 'Bill Properties',
        'class' => 'col-md-6'];

    // bottom row div
    $form['fieldset']['submit'] = ['div'   => 'bottom',
        'style' => 'padding: 0px;',
        'class' => 'col-md-12'];
*/

    $form['row'][1]['bill_name'] = [
        'type'     => 'text',
        'fieldset' => 'bill',
        'name'     => 'Description',
        'width'    => '250px',
        'readonly' => $readonly,
        'value'    => ''];

    $form['row'][2]['bill_type'] = [
        'type'     => 'select',
        'fieldset' => 'bill',
        'name'     => 'Type',
        'width'    => '250px',
        'readonly' => $readonly,
        'values'   => ['cdr'   => 'CDR / 95th',
            'quota' => 'Transfer Quota'],
        'value'    => 'cdr'];

    $form['row'][3]['bill_cdr'] = [
        'type'        => 'text',
        'fieldset'    => 'bill',
        'name'        => 'CDR/95th Threshold',
        'placeholder' => '128',
        'width'       => '120px',
        'readonly'    => $readonly,
        'value'       => ''];

    $form['row'][3]['bill_cdr_type'] = [
        'type'     => 'select',
        'fieldset' => 'bill',
        'name'     => '',
        'width'    => '120px',
        'readonly' => $readonly,
        'values'   => ['Kbps' => 'Kilobits/sec',
            'Mbps' => 'Megabits/sec',
            'Gbps' => 'Gigabits/sec'],
        'value'    => ''];


    $form['row'][4]['bill_quota'] = [
        'type'        => 'text',
        'fieldset'    => 'bill',
        'name'        => 'Usage Quota',
        'placeholder' => '128',
        'width'       => '120px',
        'readonly'    => $readonly,
        'value'       => ''];

    $form['row'][4]['bill_quota_type'] = [
        'type'     => 'select',
        'fieldset' => 'bill',
        'name'     => '',
        'width'    => '120px',
        'readonly' => $readonly,
        'values'   => ['MB' => 'Megabytes',
            'GB' => 'Gigabytes',
            'TB' => 'Terabytes'],
        'value'    => $vars['bill_quota_type']];

    $days = [];
    for ($x = 1; $x < 32; $x++) {
        $days[$x] = $x;
    }

    $form['row'][5]['bill_day'] = [
        'type'     => 'select',
        'fieldset' => 'bill',
        'name'     => 'Billing Day',
        'width'    => '120px',
        'readonly' => $readonly,
        'values'   => $days,
        'value'    => '1'];


    $form['row'][6]['bill_custid'] = [
        'type'     => 'text',
        'fieldset' => 'bill',
        'name'     => 'Customer ID',
        'width'    => '250px',
        'readonly' => $readonly,
        'value'    => ''];

    $form['row'][7]['bill_ref'] = [
        'type'     => 'text',
        'fieldset' => 'bill',
        'name'     => 'Reference',
        'width'    => '250px',
        'readonly' => $readonly,
        'value'    => ''];

    $form['row'][8]['bill_notes'] = [
        'type'     => 'textarea',
        'fieldset' => 'bill',
        'name'     => 'Notes',
        'width'    => '250px',
        'readonly' => $readonly,
        'value'    => ''];

    if (is_numeric($vars['port'])) {
        $billingport = dbFetchRow("SELECT * FROM `ports` AS P, `devices` AS D WHERE `port_id` = ? AND D.device_id = P.device_id", [$vars['port']]);

        $form['fieldset']['ports'] = ['div'   => 'top',
            'title' => 'Billed Ports',
            //'right' => TRUE,
            'class' => 'col-md-6 col-md-pull-0'];

        $form['row'][99]['port'] = [
            'type'  => 'hidden',
            'value' => $billingport['port_id']];


        $form['row'][80]['port_device'] = [
            'type'     => 'text',
            'fieldset' => 'ports',
            'disabled' => TRUE,
            'name'     => 'Device',
            'width'    => '250px',
            'readonly' => $readonly,
            'value'    => $billingport['hostname']];
        $form['row'][81]['port_device'] = [
            'type'     => 'text',
            'fieldset' => 'ports',
            'disabled' => TRUE,
            'name'     => 'Port',
            'width'    => '250px',
            'readonly' => $readonly,
            'value'    => $billingport['port_label']];
    }

$form['row'][99]['close']  = [
    'type'      => 'submit',
    'fieldset'  => 'footer',
    'div_class' => '', // Clean default form-action class!
    'name'      => 'Close',
    'icon'      => '',
    'attribs'   => ['data-dismiss' => 'modal',
        'aria-hidden'  => 'true']];

    $form['row'][99]['submit'] = [
        'type'     => 'submit',
        'fieldset' => 'submit',
        'name'     => 'Save Changes',
        'icon'     => 'icon-ok icon-white',
        //'right'       => TRUE,
        'class'    => 'btn-primary',
        'readonly' => $readonly,
        'value'    => 'add_bill'];

echo generate_form_modal($form);
unset($form, $form_params);

?>

<script type="text/javascript">
    $("#bill_type").change(function () {
        var select = this.value;
        if (select === 'cdr') {
            $('#bill_cdr_div').show();
            $("#bill_quota_div").hide();
        } else {
            $('#bill_quota_div').show();
            $('#bill_cdr_div').hide();
        }
    }).change();
</script>