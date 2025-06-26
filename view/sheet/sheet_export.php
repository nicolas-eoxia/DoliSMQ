<?php
/* Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/sheet/sheet_export.php
 * \ingroup digiquali
 * \brief   Page to export sheet and linked element (question/questiongroup) on sheet
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';
require_once __DIR__ . '/../../class/sheet.class.php';
require_once __DIR__ . '/../../class/answer.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Sheet($db);
$answer = new Answer($db);

$hookmanager->initHooks([$object->element . 'export', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

$upload_dir = getMultidirOutput($object, $object->module);

// Permissions
$permissionToRead = $user->hasRight($object->module, $object->element, 'read');
$permissionToAdd  = $user->hasRight($object->module, $object->element, 'write');

// Security check
saturne_check_access($permissionToRead, $object);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'export' && $permissionToAdd) {
        $digiqualiExportArray = $object->export();

        $fileDir    = $upload_dir . '/temp/';
        $exportName = str_replace(' ', '_', (!empty($object->label) ? $object->label : $object->ref));
        $fileName   = dol_sanitizeFileName(dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_' . dol_strtolower($exportName) . '_export');
        $fullName   = $fileDir . $fileName . '.json';

        if (!is_dir($fileDir)) {
            dol_mkdir($fileDir);
        }

        file_put_contents($fullName, json_encode($digiqualiExportArray, JSON_PRETTY_PRINT));

        $zip = new ZipArchive();
        $zipFileName = $fileDir . $fileName . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($fullName, basename($fullName));
            $zip->close();

            $filepath = DOL_URL_ROOT . '/document.php?modulepart=' . $object->module . '&file=' . urlencode('temp/' . $fileName . '.zip');
            ?>
            <script>
                const alink = document.createElement('a');
                alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
                alink.setAttribute('download', <?php echo json_encode($fileName . '.zip'); ?>);
                alink.click();
            </script>
            <?php
            setEventMessages($langs->transnoentities('ExportWellDone'), []);
        } else {
            setEventMessages($langs->transnoentities('ExportFailed'), [], 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('Export');
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $helpUrl);
print $object->getQuestionAndGroupsTree();
print '<div id="cardContent" class="margin-for-tree">';

saturne_get_fiche_head($object, 'export', $title);
saturne_banner_tab($object);

print load_fiche_titre($langs->trans('ExportSheetData'), '', '');

print '<form name="export_sheet_data" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="export">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ExportSheetData');
print '</td><td>';
print $langs->trans('ExportSheetDataDescription');
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button reposition" value="' . $langs->trans('ExportData') . '">';
print '</td></tr>';

print '</table>';
print '</form>';

print $langs->trans('ToImportDataGoToImportPage') . ' <a href="' . dol_buildpath('custom/digiquali/view/digiqualitools.php', 1) . '">' . $langs->trans('ClickHere') . '</a>';

print '</div>';
// End of page
llxFooter();
$db->close();
