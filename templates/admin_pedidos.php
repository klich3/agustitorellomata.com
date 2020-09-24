<?php

global $CONFIG, $fn_page_args, $db, $too_login;

if(!class_exists('tooLogin')) header("Location: {$CONFIG['site']['base']}error");
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}admin/login");
if($too_login->isLogged() == 200 && $too_login->isAuth(100, false) !== 200) header("Location: {$CONFIG['site']['base']}{$st_lang}/mi-cuenta");

$fn_pay_status = array(
	0 => '<span class="uk-text-danger">Sin pagar</span>',
	1 => '<span class="uk-text-success">Pagado</span>',
	2 => '<span class="uk-text-warning">Devuelto</span>',
);

$fn_entrega_status = array(
	0 => '<span class="uk-text-danger">En proceso de envío</span>',
	1 => '<span class="uk-text-warning">Enviado</span>',
	2 => '<span class="uk-text-success">Entregado</span>',
);


$fn_q_orders = $db->FetchAll("
	SELECT *
	FROM `orders`
	ORDER BY `date` DESC;
");

if($fn_q_orders)
{
	require_once('class/redsys_soap/Messages.php');
	
	foreach($fn_q_orders as $ok => $ov)
	{
		$fn_for_data = object_to_array($ov);
		
		$fn_user_email = $db->FetchValue("
			SELECT `user_email`
			FROM `users`
			WHERE `ID`=:id
			LIMIT 1;
		", array(
			'id' => $ov->user_id,
		));
		
		$fn_for_data['user_email'] = ($fn_user_email) ? $fn_user_email : '';
		$fn_for_data['payment_status'] = $fn_pay_status[$ov->payment_status];
		$fn_for_data['entrega_status'] = $fn_entrega_status[$ov->entrega_status];
		$fn_for_data['num_seg'] = (!empty($ov->num_seg)) ? $ov->num_seg : '<span class="uk-text-danger">Sin asignar</span>';
		
		$fn_for_data['tr_class'] = ($ov->payment_status) ? 'bg-green' : 'bg-grey';
		
		$fn_redsys_response = '';
		
		if($ov->payment_type == "redsys")
		{
			$fn_response = base64_decode($ov->data_response);
			$fn_response = json_decode($fn_response, true);
			
			$fn_Error = array();
			
			if(isset($fn_response['Ds_AuthorisationCode']) && empty($fn_response['Ds_AuthorisationCode']) || !preg_match('/[a-zA-Z0-9]+\ ?/', $fn_response['Ds_AuthorisationCode'])) $fn_Error[] = 'Sin código de autorización.';
			
			if(isset($fn_response['Ds_ErrorCode']))
			{
				$fn_redsys_error = \Redsys\Messages\Messages::getByCode($fn_response['Ds_ErrorCode']);
				$fn_redsys_error = (is_null($fn_redsys_error)) ? "" : "- {$fn_redsys_error['message']}";
				$fn_Error[] = "Hay un error presente ({$fn_response['Ds_ErrorCode']}) {$fn_redsys_error}";
			}
			
			$fn_redsys_response = (sizeof($fn_Error) !== 0) ?  '<i class="uk-icon-exclamation-circle uk-text-danger"></i> '. implode('<br/>', $fn_Error) : '<i class="uk-icon-check-circle uk-text-success"></i> Transacción correcta';
		}
		
		$fn_for_data['redsys_response'] = ($ov->payment_type == "redsys") ? $fn_redsys_response : "(Paypal) No disponible, solo Redsys";
		
		$fn_xtemplate_parse['assign'][] = $fn_for_data;
		$fn_xtemplate_parse['parse'][] = 'admin_pedidos.list.row';
	}
	
	$fn_xtemplate_parse['assign'][] = $fn_for_data;
	$fn_xtemplate_parse['parse'][] = 'admin_pedidos.list';
}else{
	$fn_xtemplate_parse['assign'][] = array(
		'type' => 'warning',
		'message' => 'Ahora mismo no hay pedidos',
	);
	$fn_xtemplate_parse['parse'][] = 'admin_pedidos.message';
}

?>