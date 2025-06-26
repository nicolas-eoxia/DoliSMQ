<?php
/* Copyright (C) 2023-2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/digiqualitools.php
 * \ingroup digiquali
 * \brief   Tools page of digiquali left menu
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

// Load DigiQuali libraries
require_once __DIR__ . '/../lib/digiquali.lib.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/answer.class.php';
require_once __DIR__ . '/../class/questiongroup.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize technical objects
$object        = new Sheet($db);
$question      = new Question($db);
$answer        = new Answer($db);
$questionGroup = new QuestionGroup($db);

$hookmanager->initHooks([$object->module . 'tools', 'globalcard']); // Note that conf->hooks_modules contains array

$error      = 0;
$now        = dol_now();
$upload_dir = getMultidirOutput($object, $object->module);

// Permissions
$permissionToReadSheet         = $user->hasRight($object->module, $object->element, 'read');
$permissionToReadQuestionGroup = $user->hasRight($questionGroup->module, $questionGroup->element, 'read');
$permissionToReadQuestion      = $user->hasRight($question->module, $question->element, 'read');
$permissionToRead              = $user->hasRight($object->module, 'read');
$permissionToAddSheet          = $user->hasRight($object->module, $object->element, 'write');
$permissionToAddQuestionGroup  = $user->hasRight($questionGroup->module, $questionGroup->element, 'write');
$permissionToAddQuestion       = $user->hasRight($question->module, $question->element, 'write');

// Security check
saturne_check_access($permissionToRead);

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'export' && $permissionToRead && $permissionToReadSheet && $permissionToReadQuestionGroup && $permissionToReadQuestion) {
        $digiqualiExportArray = [];
        $exportName           = '';
        if (GETPOST('export_sheet_question_answer')) {
            $exportName = 'all_models';
            $sheets     = $object->fetchAll();
            if (!is_array($sheets) || empty($sheets)) {
                setEventMessages($langs->transnoentities('ObjectNotFound', img_picto('', $object->picto, 'class="paddingrightonly"') . $langs->transnoentities(ucfirst($object->element))), [], 'errors');
                $error++;
            }

            foreach ($sheets as $sheet) {
                $digiqualiExportArray = array_merge($digiqualiExportArray, $sheet->export());
            }
        }

        if (GETPOST('export_question_answer')) {
            $exportName           = 'all_questions';
            $digiqualiExportArray = $question->export();
        }

        $fileDir    = $upload_dir . '/temp/';
        $exportBase = $fileDir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export';
        $fileName   = $exportBase . '.json';

        file_put_contents($fileName, json_encode($digiqualiExportArray, JSON_PRETTY_PRINT));

        $zip         = new ZipArchive();
        $fileNameZip = $exportBase . '.zip';
        if ($zip->open($fileNameZip, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($fileName, basename($fileName));
            $zip->close();

            $filepath = DOL_URL_ROOT . '/document.php?modulepart=' . $object->module . '&file=' . urlencode('temp/' . $fileNameZip);

            ?>
            <script>
                var alink = document.createElement( 'a' );
                alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
                alink.setAttribute('download', <?php echo json_encode($fileNameZip); ?>);
                alink.click();
            </script>
            <?php
            setEventMessage($langs->transnoentities('ExportWellDone'));
        }
    }

    // Import ZIP file
    if (GETPOST('dataMigrationImportZip', 'alpha') && $permissionToAddSheet && $permissionToAddQuestionGroup && $permissionToAddQuestion) {
        if (!empty($_FILES)) {
            if ($_FILES['dataMigrationImportZipFile']['size'][0] < 1) {
                setEventMessages($langs->trans('ErrorArchiveNotWellFormattedZIP'), [], 'errors');
            } else {
                if (is_array($_FILES['dataMigrationImportZipFile']['tmp_name'])) {
                    $userFiles = $_FILES['dataMigrationImportZipFile']['tmp_name'];
                } else {
                    $userFiles = array($_FILES['dataMigrationImportZipFile']['tmp_name']);
                }

                foreach ($userFiles as $key => $userFile) {
                    if (empty($_FILES['dataMigrationImportZipFile']['tmp_name'][$key])) {
                        $error++;
                        if ($_FILES['dataMigrationImportZipFile']['error'][$key] == 1 || $_FILES['dataMigrationImportZipFile']['error'][$key] == 2) {
                            setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
                        } else {
                            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), [], 'errors');
                        }
                    }
                }

                $result = 0;
                if (!$error) {
                    $fileDir = $upload_dir . '/temp/';
                    if (!empty($fileDir)) {
                        $result = dol_add_file_process($fileDir, 1, 1, 'dataMigrationImportZipFile', '', null, '', 0);
                    }
                }

                if ($result > 0) {
                    $zip = new ZipArchive;
                    if ($zip->open($fileDir . $_FILES['dataMigrationImportZipFile']['name'][0]) === TRUE) {
                        $zip->extractTo($fileDir);
                        $zip->close();
                    }
                }
                $fileName = preg_replace('/\.zip/', '.json', $_FILES['dataMigrationImportZipFile']['name'][0]);

                $json                  = file_get_contents($fileDir . $fileName);
                $digiqualiExportArray  = json_decode($json, true);
                $importKey             = dol_print_date($now, 'dayhourlog');
                $idCorrespondanceArray = [];
                $error                 = 0;

                if (is_array($digiqualiExportArray[$question->element]) && !empty($digiqualiExportArray[$question->element])) {
                    foreach ($digiqualiExportArray[$question->element] as $questionSingleId => $questionSingle) {
                        $question->ref_ext                = $questionSingle['ref'];
                        $question->entity                 = $conf->entity;
                        $question->status                 = $questionSingle['status'];
                        $question->type                   = $questionSingle['type'];
                        $question->label                  = $questionSingle['label'];
                        $question->description            = $questionSingle['description'];
                        $question->show_photo             = $questionSingle['show_photo'];
                        $question->authorize_answer_photo = $questionSingle['authorize_answer_photo'];
                        $question->enter_comment          = $questionSingle['enter_comment'];
                        $question->import_key             = $importKey;

                        $questionId = $question->create($user);
                        if ($questionId > 0) {
                            $idCorrespondanceArray[$question->element][$questionSingleId] = $questionId;
                            if (array_key_exists($answer->element, $questionSingle) && !empty($questionSingle[$answer->element])) {
                                foreach ($questionSingle[$answer->element] as $answerSingle) {
                                    $answer->ref_ext     = $answerSingle['ref'];
                                    $answer->entity      = $conf->entity;
                                    $answer->status      = $answerSingle['status'];
                                    $answer->value       = $answerSingle['value'];
                                    $answer->position    = $answerSingle['position'];
                                    $answer->color       = $answerSingle['color'];
                                    $answer->pictogram   = $answerSingle['pictogram'];
                                    $answer->fk_question = $questionId;
                                    $answer->import_key  = $importKey;

                                    $answerId = $answer->create($user);
                                    if ($answerId <= 0) {
                                        $error++;
                                    }
                                }
                            }
                        } else {
                            $error++;
                        }
                    }
                }

                if (is_array($digiqualiExportArray[$questionGroup->element]) && !empty($digiqualiExportArray[$questionGroup->element])) {
                    foreach($digiqualiExportArray[$questionGroup->element] as $questionGroupId => $questionGroupSingle) {
                        $previousQuestionGroup = new QuestionGroup($db);
                        $previousQuestionGroup->fetch($questionGroupId);
                        $previousQuestions = $previousQuestionGroup->fetchQuestionsOrderedByPosition();

                        $questionGroup->entity        = $conf->entity;
                        $questionGroup->status = $questionGroupSingle['status'];
                        $questionGroup->label = $questionGroupSingle['label'];
                        $questionGroup->description = $questionGroupSingle['description'];

                        $questionGroupId = $questionGroup->create($user);

                        if ($questionGroupId > 0) {
                            $idCorrespondanceArray[$questionGroup->element][$questionGroupId] = $questionGroupId;
                            if (is_array($previousQuestions) && !empty($previousQuestions)) {
                                foreach ($previousQuestions as $previousQuestion) {
                                    $previousQuestionId = $previousQuestion->id;
                                    $newQuestionId = $previousQuestion->create($user);

                                    if ($newQuestionId > 0) {
                                        $questionGroup->addQuestion($newQuestionId);
                                        $previousAnswers = $answer->fetchAll('', '', 0 , 0, ['customsql' => 'fk_question = ' . $previousQuestionId]);

                                        if (is_array($previousAnswers) && !empty($previousAnswers)) {
                                            foreach ($previousAnswers as $previousAnswer) {
                                                $previousAnswer->fk_question = $newQuestionId;
                                                $newAnswerId = $previousAnswer->create($user);
                                                if ($newAnswerId <= 0) {
                                                    $error++;
                                                }
                                            }
                                        }

                                    }
                                }
                            }

                        }
                    }
                }

                if (is_array($digiqualiExportArray[$object->element]) && !empty($digiqualiExportArray[$object->element])) {
                    foreach ($digiqualiExportArray[$object->element] as $sheetSingleId => $sheetSingle) {
                        $object->ref_ext             = $sheetSingle['ref'];
                        $object->entity              = $conf->entity;
                        $object->status              = $sheetSingle['status'];
                        $object->type                = $sheetSingle['type'];
                        $object->label               = $sheetSingle['label'];
                        $object->description         = $sheetSingle['description'];
                        $object->element_linked      = $sheetSingle['element_linked'];
                        $object->photo               = $sheetSingle['photo'];
                        $object->success_rate        = $sheetSingle['success_rate'];
                        $object->mandatory_questions = $sheetSingle['mandatory_questions'];
                        $object->import_key          = $importKey;

                        $sheetMandatoryQuestions = json_decode($sheetSingle['mandatory_questions']);
                        if (is_array($sheetMandatoryQuestions) && !empty($sheetMandatoryQuestions)) {
                            foreach($sheetMandatoryQuestions as $sheetMandatoryQuestionId) {
                                $newQuestionIdToLink = $idCorrespondanceArray[$question->element][$sheetMandatoryQuestionId];
                                $questionsToLink[] = $newQuestionIdToLink;
                            }
                            $object->mandatory_questions = json_encode($questionsToLink);
                        } else {
                            $object->mandatory_questions = '{}';
                        }

                        $sheetId = $object->create($user);
                        if ($sheetId > 0) {
                            $idCorrespondanceArray[$object->element][$sheetSingleId] = $sheetId;
                            if (is_array($digiqualiExportArray[$questionGroup->table_element]) && !empty($digiqualiExportArray[$questionGroup->table_element])) {
                                foreach ($digiqualiExportArray[$questionGroup->table_element] as $previousSheetId => $previousQuestionGroupIdArray) {
                                    if (is_array($previousQuestionGroupIdArray) && !empty($previousQuestionGroupIdArray)) {
                                        foreach($previousQuestionGroupIdArray as $previousQuestionGroupId) {
                                            $newSheetId         = $idCorrespondanceArray[$object->element][$previousSheetId];
                                            $newQuestionGroupId = $idCorrespondanceArray[$questionGroup->element][$previousQuestionGroupId];
                                            $questionGroup->fetch($newQuestionGroupId);
                                            $questionGroup->add_object_linked($object->table_element, $newSheetId);
                                        }
                                    }
                                }
                            }
                            if (is_array($digiqualiExportArray[$question->table_element]) && !empty($digiqualiExportArray[$question->table_element])) {
                                foreach ($digiqualiExportArray[$question->table_element] as $previousSheetId => $previousQuestionIdArray) {
                                    if (is_array($previousQuestionIdArray) && !empty($previousQuestionIdArray)) {
                                        foreach($previousQuestionIdArray as $previousQuestionId) {
                                            $newSheetId    = $idCorrespondanceArray[$object->element][$previousSheetId];
                                            $newQuestionId = $idCorrespondanceArray[$question->element][$previousQuestionId];
                                            $question->fetch($newQuestionId);
                                            $question->add_object_linked($object->table_element, $newSheetId);
                                        }
                                    }
                                }
                            }
                            $object->fetch($sheetId);
                            $object->fetchObjectLinked($sheetId, $object->table_element, null, '', 'OR', 1, 'position', 0);
                            $questionGroupIds   = $object->linkedObjectsIds[$questionGroup->table_element];
                            $questionIds        = $object->linkedObjectsIds[$question->table_element];
                            $object->updateQuestionsAndGroupsPosition($questionIds, $questionGroupIds);
                        } else {
                            $error++;
                        }
                    }
                    $sheetCount = count($digiqualiExportArray[$object->element]);
                    setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Sheets'), $error, $sheetCount));
                }

                $questionCount = count($digiqualiExportArray[$question->element]);
                setEventMessage($langs->transnoentities("ImportFinishWith", $langs->trans('Questions'), $error, $questionCount));
                setEventMessage($langs->transnoentities("FileWasImported", $importKey));
            }
        }
    }
}

/*
 * View
 */

$title    = $langs->trans('Tools');
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title);

print load_fiche_titre($title, '', 'wrench');

print load_fiche_titre($langs->trans('DataMigrationDigiQualiToFile'), '', '');

print '<form name="data_migration_export_global" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="export">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

// Export sheets, questions and answers data from DigiQuali
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportSQA');
print '</td><td>';
print $langs->trans('DataMigrationExportSQADescription');
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button reposition data-migration-submit" name="export_sheet_question_answer" value="' . $langs->trans("ExportData") . '">';
print '</td></tr>';

// Export questions and answers data from DigiQuali
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportQA');
print '</td><td>';
print $langs->trans('DataMigrationExportQADescription');
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button reposition data-migration-submit" name="export_question_answer" value="' . $langs->trans("ExportData") . '">';
print '</td></tr>';
print '</form>';

print load_fiche_titre($langs->trans("DataMigrationFileToDolibarr"), '', '');

print '<form name="DataMigration" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportZIP');
print "</td><td>";
print $langs->trans('DataMigrationImportZIPDescription');
print '</td>';

print '<td class="center">';
print '<input class="flat" type="file" name="dataMigrationImportZipFile[]" accept=".zip"/>';
print '<input type="submit" class="wpeo-button button reposition data-migration-submit" name="dataMigrationImportZip" value="' . $langs->trans("Upload") . '">';
print '</td></tr>';

print '</form>';

// Page end
llxFooter();
$db->close();
