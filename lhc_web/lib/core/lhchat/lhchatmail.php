<?php

class erLhcoreClassChatMail {

	// Prepare template variables
    public static function prepareSendMail(erLhAbstractModelEmailTemplate & $sendMail)
    {
    	$currentUser = erLhcoreClassUser::instance();
    	$userData = $currentUser->getUserData();
    	$sendMail->subject = str_replace(array('{name_surname}'),array($userData->name.' '.$userData->surname),$sendMail->subject);
    	$sendMail->from_name = str_replace(array('{name_surname}'),array($userData->name.' '.$userData->surname),$sendMail->from_name);

    	if (empty($sendMail->from_email)) {
    		$sendMail->from_email = $userData->email;
    	}

    	if (empty($sendMail->reply_to)) {
    		$sendMail->reply_to = $userData->email;
    	}
    }

    // Validate send mail
    public static function validateSendMail(erLhAbstractModelEmailTemplate & $sendMail, erLhcoreClassModelChat & $chat)
    {
    	$Errors = array();

    	$validationFields = array();
    	$validationFields['Message'] = new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw');
    	$validationFields['Subject'] = new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw');
    	$validationFields['FromName'] = new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw');
    	$validationFields['FromEmail'] = new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'validate_email');
    	$validationFields['ReplyEmail'] = new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'validate_email');

    	$form = new ezcInputForm( INPUT_POST, $validationFields );
    	$Errors = array();

    	$messages = array_reverse(erLhcoreClassModelmsg::getList(array('limit' => 100,'sort' => 'id DESC','filter' => array('chat_id' => $chat->id))));

    	// Fetch chat messages
    	$tpl = new erLhcoreClassTemplate( 'lhchat/messagelist/plain.tpl.php');
    	$tpl->set('chat', $chat);
    	$tpl->set('messages', $messages);

    	$sendMail->content = str_replace(array('{user_chat_nick}','{messages_content}'), array($chat->nick,$tpl->fetch()), $sendMail->content);

    	if ($form->hasValidData( 'Message' ) )
    	{
    		$sendMail->content = str_replace('{additional_message}', $form->Message, $sendMail->content);
    	}

    	if ( $form->hasValidData( 'FromEmail' ) ) {
    		$sendMail->from_email = $form->FromEmail;
    	}

    	if ( $form->hasValidData( 'ReplyEmail' ) ) {
    		$sendMail->reply_to = $form->ReplyEmail;
    	}

    	if ( $form->hasValidData( 'FromName' ) ) {
    		$sendMail->from_name = $form->FromName;
    	}

    	if ( $form->hasValidData( 'Subject' ) ) {
    		$sendMail->subject = $form->Subject;
    	}

    	if (empty($chat->email)) {
    		$Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/sendmail','User did not entered his e-mail!');
    	}

    	if (empty($sendMail->from_email)) {
    		$Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/sendmail','From e-mail is missing!');
    	}

    	if (empty($sendMail->reply_to)) {
    		$Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/sendmail','Reply e-mail is missing!');
    	}

    	if (empty($sendMail->subject)) {
    		$Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/sendmail','Subject is missing!');
    	}

    	return $Errors;
    }

    // Send mail
    public static function sendMail(erLhAbstractModelEmailTemplate & $sendMail, erLhcoreClassModelChat & $chat) {

    	$mail = new PHPMailer();
    	$mail->CharSet = "UTF-8";
    	$mail->Sender = $mail->From = $sendMail->from_email;
    	$mail->FromName = $sendMail->from_name;
    	$mail->Subject = $sendMail->subject;
    	$mail->AddReplyTo($sendMail->reply_to,$sendMail->from_name);

    	$mail->Body = $sendMail->content;
    	$mail->AddAddress( $chat->email, $chat->nick);

    	$mail->Send();
    	$mail->ClearAddresses();
    }

}

?>