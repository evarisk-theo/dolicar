<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       view/quickcreation.php
 *  \ingroup    dolicar
 *  \brief      Page to quick creation project/task
 */

// Load EasyCRM environment
if (file_exists('../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../dolicar.main.inc.php';
} elseif (file_exists('../../dolicar.main.inc.php')) {
	require_once __DIR__ . '/../../dolicar.main.inc.php';
} else {
	die('Include of dolicar main fails');
}

// Libraries
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
}
if (isModEnabled('societe')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
}
if (isModEnabled('fckeditor')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
}
if (isModEnabled('categorie')) {
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

require_once __DIR__ . '/../../class/registrationcertificatefr.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['categories']);

// Get parameters
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'quickcretion'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new RegistrationCertificateFr($db);

if (isModEnabled('project')) {
	$project = new Project($db);
	$task = new Task($db);
}
if (isModEnabled('categorie')) {
	$category = new Categorie($db);
}
if (isModEnabled('societe')) {
	$thirdparty = new Societe($db);
	$contact    = new Contact($db);
}

// Initialize view objects
$form = new Form($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
if (isModEnabled('societe')) {
	$formcompany = new FormCompany($db);
}

$hookmanager->initHooks(['dolicar_quickcreation']); // Note that conf->hooks_modules contains array

$date_start = dol_mktime(0, 0, 0, GETPOST('projectstartmonth', 'int'), GETPOST('projectstartday', 'int'), GETPOST('projectstartyear', 'int'));

// Security check - Protection if external user
$permissiontoread          = $user->rights->dolicar->read && isModEnabled('easycrm');
$permissiontoaddproject    = $user->rights->projet->creer;
$permissiontoaddthirdparty = $user->rights->societe->creer;
$permissiontoaddcontact    = $user->rights->societe->contact->creer;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

$parameters = [];
$reshook = $hookmanager->executeHooks('doActions', $parameters, $project, $action); // Note that $action and $project may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	if ($cancel) {
		header('Location: ' . dol_buildpath('/dolicar/dolicarindex.php', 1));
		exit;
	}
	require_once __DIR__ . '/../../../easycrm/core/tpl/easycrm_quickcreation_actions.tpl.php';
}

/*
 * View
 */

$title    = $langs->trans('QuickCreation');
$help_url = 'FR:Module_EasyCRM';

saturne_header(0, '', $title, $help_url);

if (empty($permissiontoaddthirdparty) && empty($permissiontoaddcontact) && empty($permissiontoaddproject)) {
	accessforbidden($langs->trans('NotEnoughPermissions'), 0);
	exit;
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="add">';
if ($backtopage) {
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
}

//Ajouter le code carte grise
print load_fiche_titre($langs->trans("QuickRegistrationCertificateCreation"), '', 'object_'.$object->picto);

print '<hr>';
print '<table class="border centpercent tableforfieldcreate">';
print '<tr>';
print '<td class="titlefieldcreate fieldrequired">';
print $langs->trans('LicencePlate');
print '</td>';
print '<td class="valuefieldcreate">';
print '<input class="flat minwidth400 --success" id="registrationNumber" name="registrationNumber" value="'. GETPOST('registrationNumber') .'">';
print '</td>';
print '</tr>';
print '<tr>';
print '</tr>';
print '</table>';
print '<hr>';
print '<br>';

if ($conf->global->DOLICAR_THIRDPARTY_QUICK_CREATION) {
	require_once __DIR__ . '/../../../easycrm/core/tpl/easycrm_thirdparty_quickcreation.tpl.php';
}

if ($conf->global->DOLICAR_CONTACT_QUICK_CREATION) {
	require_once __DIR__ . '/../../../easycrm/core/tpl/easycrm_contact_quickcreation.tpl.php';
}

if ($conf->global->DOLICAR_PROJECT_QUICK_CREATION) {
	require_once __DIR__ . '/../../../easycrm/core/tpl/easycrm_project_quickcreation.tpl.php';
}

print $form->buttonsSaveCancel('Create');

print '</form>';

// End of page
llxFooter();
$db->close();
