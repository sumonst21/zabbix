<?php
/*
** Zabbix
** Copyright (C) 2001-2018 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/classes/user/CWebUser.php';
CWebUser::disableSessionCookie();
CWebUser::disableGuestAutoLogin();

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

$page['title'] = _('ZABBIX');
$page['file'] = 'index.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'name' =>		[T_ZBX_STR, O_NO,	null,	null,	'isset({enter})', _('Username')],
	'password' =>	[T_ZBX_STR, O_OPT, null,	null,	'isset({enter})'],
	'sessionid' =>	[T_ZBX_STR, O_OPT, null,	null,	null],
	'reconnect' =>	[T_ZBX_INT, O_OPT, P_SYS,	null,	null],
	'enter' =>		[T_ZBX_STR, O_OPT, P_SYS,	null,	null],
	'autologin' =>	[T_ZBX_INT, O_OPT, null,	null,	null],
	'request' =>	[T_ZBX_STR, O_OPT, null,	null,	null],
	'guest_login' => [T_ZBX_INT, O_OPT, null,	null,	null],
	'form' =>		[T_ZBX_STR, O_OPT, null,	null,	null]
];
check_fields($fields);

/**
 * When HTTP authentication is enabled attempt to silently login as guest by opening any URL except this one
 * will fail to HTTP authentication therefore we have to login guest user explicitly.
 */
if (hasRequest('guest_login')) {
	// Remove HTTP authentication step messages if any exists.
	clear_messages();
	CWebUser::login(ZBX_GUEST_USER, '');
	redirect(ZBX_DEFAULT_URL);

	exit;
}

if (hasRequest('reconnect') && CWebUser::isLoggedIn()) {
	CWebUser::logout();
}

$config = select_config();
$autologin = hasRequest('enter') ? getRequest('autologin', 0) : getRequest('autologin', 1);
$request = getRequest('request', '');

if ($request) {
	$test_request = [];
	preg_match('/^\/?(?<filename>[a-z0-9\_\.]+\.php)(?<request>\?.*)?$/i', $request, $test_request);

	$request = (array_key_exists('filename', $test_request) && file_exists('./'.$test_request['filename']))
		? $test_request['filename'].(array_key_exists('request', $test_request) ? $test_request['request'] : '')
		: '';
}

if (!hasRequest('form') && $config['http_auth_enabled'] == ZBX_AUTH_HTTP_ENABLED
		&& $config['http_login_form'] == ZBX_AUTH_FORM_HTTP) {
	redirect('index_http.php');

	exit;
}

// login via form
if (hasRequest('enter') && CWebUser::login(getRequest('name', ''), getRequest('password', ''))) {
	if (CWebUser::$data['autologin'] != $autologin) {
		API::User()->update([
			'userid' => CWebUser::$data['userid'],
			'autologin' => $autologin
		]);
	}

	$redirect = array_filter([$request, CWebUser::$data['url'], ZBX_DEFAULT_URL]);
	redirect(reset($redirect));

	exit;
}

if (CWebUser::isLoggedIn() && !CWebUser::isGuest()) {
	redirect(CWebUser::$data['url'] ? CWebUser::$data['url'] : ZBX_DEFAULT_URL);
}

$messages = clear_messages();

(new CView('general.login', [
	'http_login_url' => $config['http_auth_enabled'] == ZBX_AUTH_HTTP_ENABLED
		? (new CUrl('index_http.php'))->removeArgument('sid')
		: '',
	'guest_login_url' => CWebUser::isGuestAllowed()
		? (new CUrl())->setArgument('guest_login', 1)
		: '',
	'autologin' => $autologin == 1,
	'error' => hasRequest('enter') && $messages ? array_pop($messages) : null
]))->render();
