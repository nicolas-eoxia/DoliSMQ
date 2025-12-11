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
 * \defgroup digiquali Module DigiQuali
 * \brief    DigiQuali module descriptor
 *
 * \file    core/modules/modDigiQuali.class.php
 * \ingroup digiquali
 * \brief   Description and activation file for module DigiQuali
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module DigiQuali
 */
class modDigiQuali extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param  DoliDB $db Database handler
     * @throws Exception
     */
    public function __construct($db)
    {
        global $conf, $langs;

        parent::__construct($db);

        if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            saturne_load_langs(['digiquali@digiquali']);
        } else {
            $this->error++;
            $this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'DigiQuali', 'Saturne');
        }

        // ID for module (must be unique)
        $this->numero = 436301;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'digiquali';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = 'evarisk';

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '02';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = ['Evarisk' => ['position' => '01', 'label' => $langs->trans('Evarisk')]];
        // Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModule is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // DESCRIPTION_FLAG
        // Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModule is name of module)
        $this->description = $langs->trans($this->name . 'Description');
        // Used only if file README.md and README-LL.md not found
        $this->descriptionlong = $langs->trans($this->name . 'DescriptionLong');

        // Author
        $this->editor_name          = 'Evarisk';
        $this->editor_url           = 'https://evarisk.com/'; // Must be an external online website
        $this->editor_squarred_logo = '';                     // Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@mymodule'

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
        $this->version = trim(file_get_contents(__DIR__ . '/../../VERSION'));
        // Url to the file with your last number version of this module
        $this->url_last_version = 'https://github.com/Evarisk/digiquali/blob/main/VERSION';

        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        // Name of image file used for this module
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
        $this->picto = $this->rights_class . '_color@' . $this->rights_class;

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = [
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 1,
            // Set this to 1 if module has its own models' directory (core/modules/xxx)
            'models' => 1,
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            /* BEGIN MODULEBUILDER HOOKSCONTEXTS */
            'hooks' => [
                'category',
                'categoryindex',
                'mainloginpage',
                'controlcard',
                'publiccontrol',
                'publicsurvey',
                'digiqualiadmindocuments',
                'projecttaskscard',
                'main',
                'controladmin',
                'surveyadmin',
            ],
            /* END MODULEBUILDER HOOKSCONTEXTS */
        ];

        // Data directories to create when module is enabled
        $this->dirs = [
            '/digiquali/temp',
            '/digiquali/question',
            '/ecm/digiquali',
            '/ecm/digiquali/medias',
            '/ecm/digiquali/controldocument',
            '/ecm/digiquali/surveydocument'
        ];

        // Config pages. Put here list of php page, stored into mymodule/admin directory, to use to set up module
        $this->config_page_url = ['setup.php@' . $this->rights_class];

        // Dependencies
        // A condition to hide module
        $this->hidden = getDolGlobalInt('MODULE_' . strtoupper($this->name) . '_DISABLED'); // A condition to disable module;
        // List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
        $this->depends = ['modFckeditor', 'modProduct', 'modProductBatch', 'modECM', 'modProjet', 'modCategorie', 'modSaturne', 'modTicket', 'modCron'];
        // List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->requiredby = [];
        // List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = [];

        // The language file dedicated to your module
        $this->langfiles = [$this->rights_class . '@' . $this->rights_class];

        // Prerequisites
        $this->phpmin                 = [7, 4];  // Minimum version of PHP required by module
        //$this->phpmax               = [8, 0;   // Maximum version of PHP required by module
        $this->need_dolibarr_version  = [19, 0]; // Minimum version of Dolibarr required by module
        //$this->max_dolibarr_version = [19, 0]; // Maximum version of Dolibarr required by module
        $this->need_javascript_ajax   = 1;

        // Messages at activation
        $this->warnings_activation     = []; // Warning to show when we activate a module. Example: array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = []; // Warning to show when we activate a module if another module is on. Example: array('modOtherModule' => array('always'=>'text')) or array('always' => array('FR'=>'textfr','MX'=>'textmx'...))
        //$this->automatic_activation  = array('FR'=>'MyModuleWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled        = false; // If true, can't be disabled. Value true is reserved for core modules. Not allowed for external modules

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        $i           = 0;
        $this->const = [
            // CONST SHEET
            $i++ => ['DIGIQUALI_SHEET_ADDON', 'chaine', 'mod_sheet_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHEET_TAGS_SET', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHEET_UNIQUE_LINKED_ELEMENT', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHEET_DISPLAY_MEDIAS', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHEET_DEFAULT_TAG', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHEET_BACKWARD_COMPATIBILITY', 'integer', 0, '', 0, 'current'],

            // CONST QUESTION
            $i++ => ['DIGIQUALI_QUESTION_ADDON', 'chaine', 'mod_question_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_QUESTIONGROUP_ADDON', 'chaine', 'mod_questiongroup_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_QUESTION_BACKWARD_COMPATIBILITY', 'integer', 1, '', 0, 'current'],

            // CONST ANSWER
            $i++ => ['DIGIQUALI_ANSWER_ADDON', 'chaine', 'mod_answer_standard', '', 0, 'current'],

			// CONST CONTROL
			$i++ => ['DIGIQUALI_CONTROL_ADDON', 'chaine', 'mod_control_standard', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROL_USE_LARGE_MEDIA_IN_GALLERY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROL_REMINDER_ENABLED', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROL_REMINDER_FREQUENCY', 'chaine', '30,60,90', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROL_REMINDER_TYPE', 'chaine', 'browser', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROL_BACKWARD_COMPATIBILITY', 'integer', 0, '', 0, 'current'],
			$i++ => ['PRODUCT_LOT_ENABLE_QUALITY_CONTROL', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_LOCK_CONTROL_OUTDATED_EQUIPMENT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_ENABLE_PUBLIC_CONTROL_HISTORY', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHOW_QC_FREQUENCY_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHOW_LAST_CONTROL_FIRST_ON_PUBLIC_HISTORY', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHOW_PARENT_LINKED_OBJECT_ON_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIQUALI_NEXT_CONTROL_DATE_COLOR_0', 'chaine', '#FF3535', '', 0, 'current'],
            $i++ => ['DIGIQUALI_NEXT_CONTROL_DATE_COLOR_30', 'chaine', '#FD7E00', '', 0, 'current'],
            $i++ => ['DIGIQUALI_NEXT_CONTROL_DATE_COLOR_60', 'chaine', '#FFB700', '', 0, 'current'],
            $i++ => ['DIGIQUALI_NEXT_CONTROL_DATE_COLOR_90', 'chaine', '#C7BA10', '', 0, 'current'],

            // CONST SURVEY
            $i++ => ['DIGIQUALI_SURVEY_ADDON', 'chaine', 'mod_survey_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SURVEY_USE_LARGE_MEDIA_IN_GALLERY', 'integer', 1, '', 0, 'current'],

            // CONST DIGIQUALI DOCUMENTS
            $i++ => ['DIGIQUALI_AUTOMATIC_PDF_GENERATION', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_MANUAL_PDF_GENERATION', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_SHOW_SIGNATURE_SPECIMEN', 'integer', 0, '', 0, 'current'],

			//CONST CONTROL DOCUMENT
			$i++ => ['DIGIQUALI_CONTROLDOCUMENT_ADDON', 'chaine', 'mod_controldocument_standard', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROLDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiquali/documents/doctemplates/controldocument/', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROLDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiquali/controldocument/', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROLDOCUMENT_DEFAULT_MODEL', 'chaine', 'template_controldocument_photo' ,'', 0, 'current'],
			$i++ => ['DIGIQUALI_DOCUMENT_MEDIA_VIGNETTE_USED', 'chaine', 'small','', 0, 'current'],

            //CONST SURVEY DOCUMENT
            $i++ => ['DIGIQUALI_SURVEYDOCUMENT_ADDON', 'chaine', 'mod_surveydocument_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SURVEYDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiquali/documents/doctemplates/surveydocument/', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SURVEYDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiquali/surveydocument/', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SURVEYDOCUMENT_DEFAULT_MODEL', 'chaine', 'template_surveydocument_photo' ,'', 0, 'current'],
            //$i++ => ['DIGIQUALI_SURVEYDOCUMENT_DISPLAY_MEDIAS', 'integer', 1,'', 0, 'current'],

			// CONST CONTROL LINE
			$i++ => ['DIGIQUALI_CONTROLDET_ADDON', 'chaine', 'mod_controldet_standard', '', 0, 'current'],
			$i++ => ['DIGIQUALI_CONTROLDET_AUTO_SAVE_ACTION', 'integer', 1, '', 0, 'current'],

			// CONST CONTROL EQUIPMENT
			$i++ => ['DIGIQUALI_CONTROL_EQUIPMENT_ADDON', 'chaine', 'mod_control_equipment_standard', '', 0, 'current'],

            // CONST SURVEY LINE
            $i++ => ['DIGIQUALI_SURVEYDET_ADDON', 'chaine', 'mod_surveydet_standard', '', 0, 'current'],
            $i++ => ['DIGIQUALI_SURVEYDET_AUTO_SAVE_ACTION', 'integer', 1, '', 0, 'current'],

            // CONST PROCESS
            $i++ => ['DIGIQUALI_PROCESS_ADDON', 'chaine', 'mod_process_standard', '', 0, 'current'],

            // CONST SUBPROCESS
            $i++ => ['DIGIQUALI_SUBPROCESS_ADDON', 'chaine', 'mod_subprocess_standard', '', 0, 'current'],

            // CONST ACTIVITY
            $i++ => ['DIGIQUALI_ACTIVITY_ADDON', 'chaine', 'mod_activity_standard', '', 0, 'current'],

            // CONST RISKASSESSMENT
            $i++ => ['DIGIQUALI_RISKASSESSMENT_ADDON', 'chaine', 'mod_riskassessment_standard', '', 0, 'current'],

			// CONST MODULE
			$i++ => ['DIGIQUALI_VERSION','chaine', $this->version, '', 0, 'current'],
			$i++ => ['DIGIQUALI_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
			$i++ => ['DIGIQUALI_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_WIDTH_MINI', 'integer', 128, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_HEIGHT_MINI', 'integer', 72, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_WIDTH_SMALL', 'integer', 480, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_HEIGHT_SMALL', 'integer', 270, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'],
			$i++ => ['DIGIQUALI_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'],
			$i++ => ['DIGIQUALI_DISPLAY_NUMBER_MEDIA_GALLERY', 'integer', 8, '', 0, 'current'],
            $i++ => ['DIGIQUALI_REDIRECT_AFTER_CONNECTION', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIQUALI_ADVANCED_TRIGGER', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIQUALI_DOCUMENT_DIRECTORIES_NAME_BACKWARD_COMPATIBILITY', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIQUALI_ANSWER_PUBLIC_INTERFACE_TITLE', 'chaine', $langs->trans('AnswerPublicInterface'), '', 0, 'current'],

            $i++ => ['AGENDA_REMINDER_BROWSER', 'integer', 1, '', 0, 'current'],
            $i++ => ['AGENDA_REMINDER_EMAIL', 'integer', 1, '', 0, 'current'],

			// CONST DOCUMENTS
			$i++ => ['MAIN_ODT_AS_PDF', 'chaine', 'libreoffice', '', 0, 'current'],
		];

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isModEnabled($this->rights_class)) {
            $conf->digiquali          = new stdClass();
            $conf->digiquali->enabled = 0;
        }

        // Array to add new pages in new tabs
        /* BEGIN MODULEBUILDER TABS */
        // Don't forget to deactivate/reactivate your module to test your changes
        $this->tabs = [];
        /* END MODULEBUILDER TABS */

        require_once __DIR__ . '/../../lib/digiquali_sheet.lib.php';

        $pictoPath       = dol_buildpath('custom/digiquali/img/digiquali_color.png', 1);
        $picto           = img_picto('', $pictoPath, '', 1, 0, 0, '', 'pictoModule');
        $objectsMetadata = saturne_get_objects_metadata();

        foreach($objectsMetadata as $objectType => $objectMetadata) {
            if (preg_match('/_/', $objectType)) {
                $splittedElementType = explode('_', $objectType);
                $moduleName = $splittedElementType[0];
                $objectName = dol_strtolower($objectMetadata['class_name']);
                $objectType = $objectName . '@' . $moduleName;
            } else {
                $objectType = $objectMetadata['tab_type'];
            }
            $this->tabs[] = ['data' => $objectType . ':+control:' . $picto . $langs->trans('Controls') . ':digiquali@digiquali:$user->rights->digiquali->control->read:/custom/digiquali/view/control/control_list.php?fromid=__ID__&fromtype=' . $objectType];
            $this->tabs[] = ['data' => $objectType . ':+survey:' . $picto . $langs->trans('Surveys') . ':digiquali@digiquali:$user->rights->digiquali->survey->read:/custom/digiquali/view/survey/survey_list.php?fromid=__ID__&fromtype=' . $objectType];

            $this->module_parts['hooks'][] = $objectMetadata['hook_name_list'];
            $this->module_parts['hooks'][] = $objectMetadata['hook_name_card'];
        }

        // Dictionaries
        /* BEGIN MODULEBUILDER DICTIONARIES */
        $this->dictionaries = [
            'langs' => 'digiquali@digiquali',
            // List of tables we want to see into dictionary editor
            'tabname' => [
                MAIN_DB_PREFIX . 'c_question_type',
                MAIN_DB_PREFIX . 'c_control_attendants_role',
                MAIN_DB_PREFIX . 'c_survey_attendants_role',
            ],
            // Label of tables
            'tablib' => [
                'Question',
                'Control',
                'Survey'
            ],
            // Request to select fields
            'tabsql' => [
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active  FROM ' . $this->db->prefix() . 'c_question_type as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . $this->db->prefix() . 'c_control_attendants_role as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . $this->db->prefix() . 'c_survey_attendants_role as f'
            ],
            // Sort order
            'tabsqlsort' => [
                'label ASC',
                'label ASC',
                'label ASC'
            ],
            // List of fields (result of select to show dictionary)
            'tabfield' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields to edit a record)
            'tabfieldvalue' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields for insert)
            'tabfieldinsert' => [
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid' => [
                'rowid',
                'rowid',
                'rowid'
            ],
            // Condition to show each dictionary
            'tabcond' => [
                isModEnabled($this->rights_class),
                isModEnabled($this->rights_class),
                isModEnabled($this->rights_class)
            ]
        ];
        /* END MODULEBUILDER DICTIONARIES */

        // Boxes/Widgets
        // Add here list of php file(s) stored in mymodule/core/boxes that contains a class to show a widget
        /* BEGIN MODULEBUILDER WIDGETS */
        $this->boxes = [];
        /* END MODULEBUILDER WIDGETS */

        // Cronjob (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        /* BEGIN MODULEBUILDER CRON */
        $this->cronjobs = [];
        /* END MODULEBUILDER CRON */

        // Permissions provided by this module
        $this->rights = [];
        $r            = 0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $o = 1;

        /* DIGIQUALI PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', ($o * 10) + $r); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->trans('ReadModule', $this->name);        // Permission label
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = 1;                                               // In php code, permission will be checked by test if ($user->hasRight('mymodule', 'myobject', 'read'))
        $r++;

        $moduleObjects = [
            'control' => [
                'read'       => $langs->transnoentities('ReadObjects', $langs->transnoentities('ControlsMin')),
                'write'      => $langs->transnoentities('CreateObjects', $langs->transnoentities('ControlsMin')),
                'delete'     => $langs->transnoentities('DeleteObjects', $langs->transnoentities('ControlsMin')),
                'setverdict' => $langs->transnoentities('CanSetVerdict')
            ],
            'question' => [
                'read'   => $langs->transnoentities('ReadObjects', $langs->transnoentities('Questions')),
                'write'  => $langs->transnoentities('CreateObjects', $langs->transnoentities('Questions')),
                'delete' => $langs->transnoentities('DeleteObjects', $langs->transnoentities('Questions'))
            ],
            'questiongroup' => [
                'read'   => $langs->transnoentities('ReadObjects', $langs->transnoentities('QuestionGroup')),
                'write'  => $langs->transnoentities('CreateObjects', $langs->transnoentities('QuestionGroup')),
                'delete' => $langs->transnoentities('DeleteObjects', $langs->transnoentities('QuestionGroup'))
            ],
            'sheet' => [
                'read'   => $langs->transnoentities('ReadObjects', $langs->transnoentities('Sheets')),
                'write'  => $langs->transnoentities('CreateObjects', $langs->transnoentities('Sheets')),
                'delete' => $langs->transnoentities('DeleteObjects', $langs->transnoentities('Sheets'))
            ],
            'survey' => [
                'read'   => $langs->transnoentities('ReadObjects', dol_strtolower($langs->transnoentities('Surveys'))),
                'write'  => $langs->transnoentities('CreateObjects', dol_strtolower($langs->transnoentities('Surveys'))),
                'delete' => $langs->transnoentities('DeleteObjects', dol_strtolower($langs->transnoentities('Surveys')))
            ],
            $this->rights_class . 'standard' => [
                'read'   => $langs->transnoentities('ReadObjects', $langs->transnoentities('DigiQualiStandards')),
                'write'  => $langs->transnoentities('CreateObjects', $langs->transnoentities('DigiQualiStandards')),
                'delete' => $langs->transnoentities('DeleteObjects', $langs->transnoentities('DigiQualiStandards'))
            ],
            $this->rights_class . 'element' => [
                'read'   => $langs->transnoentities('ReadObjects', $langs->transnoentities('DigiQualiElements')),
                'write'  => $langs->transnoentities('CreateObjects', $langs->transnoentities('DigiQualiElements')),
                'delete' => $langs->transnoentities('DeleteObjects', $langs->transnoentities('DigiQualiElements'))
            ],
            'activity' => [
                'read'   => $langs->transnoentities('ReadObjects', dol_strtolower($langs->transnoentities('Activity'))),
                'write'  => $langs->transnoentities('CreateObjects', dol_strtolower($langs->transnoentities('Activity'))),
                'delete' => $langs->transnoentities('DeleteObjects', dol_strtolower($langs->transnoentities('Activity')))
            ],
            'riskassessment' => [
                'read'   => $langs->transnoentities('ReadObjects', dol_strtolower($langs->transnoentities('RiskAssessment'))),
                'write'  => $langs->transnoentities('CreateObjects', dol_strtolower($langs->transnoentities('RiskAssessment'))),
                'delete' => $langs->transnoentities('DeleteObjects', dol_strtolower($langs->transnoentities('RiskAssessment')))
            ],
        ];
        foreach ($moduleObjects as $moduleObject => $permissionTypes) {
            foreach ($permissionTypes as $permissionType => $permissionLabel) {
                $this->rights[$r][0] = $this->numero . sprintf('%02d', ($o * 10) + $r);
                $this->rights[$r][1] = $permissionLabel;
                $this->rights[$r][4] = $moduleObject;
                $this->rights[$r][5] = $permissionType;
                $r++;
            }
        }

        /* ADMINPAGE PANEL ACCESS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', ($o * 10) + $r);
        $this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', $this->name);
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', ($o * 10) + $r);
        $this->rights[$r][1] = $langs->transnoentities('ChangeUserController');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'changeusercontroller';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', ($o * 10) + $r);
        $this->rights[$r][1] = $langs->transnoentities('UseToolsPanel');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'tools';
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = [];
        $r = 0;
        // Add here entries to declare new menus
        /* BEGIN MODULEBUILDER TOPMENU */
        $this->menu[$r++] = [
            'fk_menu'  => '',                                                                         // Will be stored into mainmenu + leftmenu. Use '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'top',                                                                      // This is a Top menu entry
            'titre'    => $this->name,
            'prefix'   => img_picto('', $this->picto, 'class="pictofixedwidth"'),
            'mainmenu' => $this->rights_class,
            'leftmenu' => '',
            'url'      => '/' . $this->rights_class . '/' . $this->rights_class . 'index.php',        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory
            'langs'    => $this->rights_class . '@' . $this->rights_class,
            'position' => 1000 + $r,
            'enabled'  => 'isModEnabled(\'' . $this->rights_class . '\')',                               // Define condition to show or hide menu entry. Use "isModEnabled('mymodule')" if entry must be visible if module is enabled (those quote marks are importants)
            'perms'    => '$user->hasRight(\'' . $this->rights_class . '\', \'read\')',                  // Use 'perms'=>'$user->hasRight("mymodule", "myobject", "read")' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 2,                                                                        // 0=Menu for internal users, 1=external users, 2=both
        ];
        /* END MODULEBUILDER TOPMENU */

        /* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=' . $this->rights_class,
            'type'     => 'left',
            'titre'    => $this->name,
            'prefix'   => img_picto('', 'fontawesome_fa-home_fas', 'class="pictofixedwidth"'),
            'mainmenu' => $this->rights_class,
            'leftmenu' => $this->rights_class . '_index',
            'url'      => '/' . $this->rights_class . '/' . $this->rights_class . 'index.php',
            'langs'    => $this->rights_class . '@' . $this->rights_class,
            'position' => 1000 + $r,
            'enabled'  => 'isModEnabled(\'' . $this->rights_class . '\')',
            'perms'    => '$user->hasRight(\'' . $this->rights_class . '\', \'read\')',
            'target'   => '',
            'user'     => 2,
        ];

        $moduleObjects = [
            'question' => 'question',
            'sheet'    => 'list',
            'control'  => 'tasks',
            'survey'   => 'marker',
        ];
        foreach ($moduleObjects as $moduleObject => $picto) {
            $this->menu[$r++] = [
                'fk_menu'  => 'fk_mainmenu=' . $this->rights_class,
                'type'     => 'left',
                'titre'    => $langs->transnoentities(dol_ucfirst($moduleObject)),
                'prefix'   => img_picto('', 'fontawesome_' . $picto . '_fas', 'class="pictofixedwidth"'),
                'mainmenu' => $this->rights_class,
                'leftmenu' => $this->rights_class . '_' . $moduleObject . '_list',
                'url'      => '/' . $this->rights_class . '/view/' . $moduleObject . '/' . $moduleObject . '_list.php',
                'langs'    => $this->rights_class . '@' . $this->rights_class,
                'position' => 1000 + $r,
                'enabled'  => 'isModEnabled(\'' . $this->rights_class . '\')',
                'perms'    => '$user->hasRight(\'' . $this->rights_class . '\', \'' . $moduleObject . '\', \'read\')',
                'target'   => '',
                'user'     => 2,
            ];
        }

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=' . $this->rights_class,
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Mapping'),
            'prefix'   => img_picto('', 'fontawesome_sitemap_fas', 'class="pictofixedwidth"'),
            'mainmenu' => $this->rights_class,
            'leftmenu' => $this->rights_class . 'standard',
            'url'      => '/' . $this->rights_class . '/view/' . $this->rights_class . 'standard/' . $this->rights_class . 'standard_card.php?module_name=' . $this->rights_class,
            'langs'    => $this->rights_class . '@' . $this->rights_class,
            'position' => 1000 + $r,
            'enabled'  => 'isModEnabled(\'' . $this->rights_class . '\')',
            'perms'    => '$user->hasRight(\'' . $this->rights_class . '\', \'' . $this->rights_class . 'standard\', \'read\')',
            'target'   => '',
            'user'     => 2,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=' . $this->rights_class,
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Tools'),
            'prefix'   => img_picto('', 'fontawesome_wrench_fas', 'class="pictofixedwidth"'),
            'mainmenu' => $this->rights_class,
            'leftmenu' => $this->rights_class . '_tools',
            'url'      => '/' . $this->rights_class . '/view/' . $this->rights_class . 'tools.php',
            'langs'    => $this->rights_class . '@' . $this->rights_class,
            'position' => 1000 + $r,
            'enabled'  => 'isModEnabled(\'' . $this->rights_class . '\')',
            'perms'    => '$user->hasRight(\'' . $this->rights_class . '\', \'adminpage\', \'tools\')',
            'target'   => '',
            'user'     => 2,
        ];
        /* END MODULEBUILDER LEFTMENU MYOBJECT */

        // Exports profiles provided by this module
        $r = 0;
        /* BEGIN MODULEBUILDER EXPORT MYOBJECT */
        /*
        $langs->load("mymodule@mymodule");
        $this->export_code[$r] = $this->rights_class.'_'.$r;
        $this->export_label[$r] = 'MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r] = $this->picto;
        // Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
        $keyforclass = 'MyObject'; $keyforclassfile='/mymodule/class/myobject.class.php'; $keyforelement='myobject@mymodule';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        //$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
        //unset($this->export_fields_array[$r]['t.fieldtoremove']);
        //$keyforclass = 'MyObjectLine'; $keyforclassfile='/mymodule/class/myobject.class.php'; $keyforelement='myobjectline@mymodule'; $keyforalias='tl';
        //include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@mymodule';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$keyforselect='myobjectline'; $keyforaliasextra='extraline'; $keyforelement='myobjectline@mymodule';
        //include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$this->export_dependencies_array[$r] = array('myobjectline' => array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
        //$this->export_special_array[$r] = array('t.field' => '...');
        //$this->export_examplevalues_array[$r] = array('t.field' => 'Example');
        //$this->export_help_array[$r] = array('t.field' => 'FieldDescHelp');
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.$this->db->prefix().'mymodule_myobject as t';
        //$this->export_sql_end[$r]  .=' LEFT JOIN '.$this->db->prefix().'mymodule_myobject_line as tl ON tl.fk_myobject = t.rowid';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
        $r++; */
        /* END MODULEBUILDER EXPORT MYOBJECT */

        // Imports profiles provided by this module
        $r = 0;
        /* BEGIN MODULEBUILDER IMPORT MYOBJECT */
        /*
        $langs->load("mymodule@mymodule");
        $this->import_code[$r] = $this->rights_class.'_'.$r;
        $this->import_label[$r] = 'MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->import_icon[$r] = $this->picto;
        $this->import_tables_array[$r] = array('t' => $this->db->prefix().'mymodule_myobject', 'extra' => $this->db->prefix().'mymodule_myobject_extrafields');
        $this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
        $import_sample = array();
        $keyforclass = 'MyObject'; $keyforclassfile='/mymodule/class/myobject.class.php'; $keyforelement='myobject@mymodule';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
        $import_extrafield_sample = array();
        $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject@mymodule';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
        $this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.$this->db->prefix().'mymodule_myobject');
        $this->import_regex_array[$r] = array();
        $this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
        $this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
        $this->import_convertvalue_array[$r] = array(
            't.ref' => array(
                'rule'=>'getrefifauto',
                'class'=>(!getDolGlobalString('MYMODULE_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('MYMODULE_MYOBJECT_ADDON')),
                'path'=>"/core/modules/mymodule/".(!getDolGlobalString('MYMODULE_MYOBJECT_ADDON') ? 'mod_myobject_standard' : getDolGlobalString('MYMODULE_MYOBJECT_ADDON')).'.php',
                'classobject'=>'MyObject',
                'pathobject'=>'/mymodule/class/myobject.class.php',
            ),
            't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
            't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
            't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
        );
        $this->import_run_sql_after_array[$r] = array();
        $r++; */
        /* END MODULEBUILDER IMPORT MYOBJECT */
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param  string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     * @throws Exception
     */
    public function init($options = ''): int
    {
        global $conf, $langs, $user;

        if ($this->error > 0) {
            setEventMessages('', $this->errors, 'errors');
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        $sql    = [];
        $result = $this->_load_tables('/' . $this->rights_class . '/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Load sql sub folders
        $sqlFolder = scandir(__DIR__ . '/../../sql');
        foreach ($sqlFolder as $subFolder) {
            if (!preg_match('/\./', $subFolder)) {
                $result = $this->_load_tables('/' . $this->rights_class . '/sql/' . $subFolder . '/');
                if ($result < 0) {
                    return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
                }
            }
        }

        // Permissions
        $this->remove($options);

        $moduleNameUpperCase = strtoupper($this->name);

        dolibarr_set_const($this->db, $moduleNameUpperCase . '_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, $moduleNameUpperCase . '_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

        // Document templates
        delDocumentModel('controldocument_odt', 'controldocument');
        delDocumentModel('surveydocument_odt', 'surveydocument');
        delDocumentModel('control_document', 'controldocument');

        addDocumentModel('controldocument_odt', 'controldocument', 'ODT templates', $moduleNameUpperCase . '_CONTROLDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('surveydocument_odt', 'surveydocument', 'ODT templates', $moduleNameUpperCase . '_SURVEYDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('control_document', 'controldocument', $langs->transnoentities('ControlDocumentPDF'));

        // Create extra fields during init
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($this->db);

        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadataType => $objectMetadata) {
            $extraFields->addExtraField('qc_frequency', 'QcFrequency', 'int', 100, 10, $objectMetadata['table_element'], 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '','',0, 'digiquali@digiquali', '$conf->digiquali->enabled');
            if ($objectMetadataType == 'productlot') {
                $extraFields->update('control_history_link', 'ControlHistoryLink', 'varchar', 255, $objectMetadata['table_element'], 0, 0, 110, '', 0, '', 5, '', '', '', 0, 'digiquali@digiquali', '$conf->digiquali->enabled');
                $extraFields->addExtraField('control_history_link', 'ControlHistoryLink', 'varchar', 110, 255, $objectMetadata['table_element'], 0, 0, '', '', 0, '', 5, '','',0, 'digiquali@digiquali', '$conf->digiquali->enabled');
            } else {
                $extraFields->delete('control_history_link', $objectMetadata['table_element']);
            }
        }

        if (!getDolGlobalInt($moduleNameUpperCase . '_SHEET_TAGS_SET') && getDolGlobalInt($moduleNameUpperCase . '_SHEET_DEFAULT_TAG') == 0) {
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

            $category = new Categorie($this->db);

            $category->label = $langs->transnoentities('Default');
            $category->type  = 'sheet';

            $result = $category->create($user);
            if ($result < 0) {
                setEventMessages($category->error, $category->errors, 'errors');
                return -1;
            }

            dolibarr_set_const($this->db, $moduleNameUpperCase . '_SHEET_DEFAULT_TAG', $category->id, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIQUALI_ACTIVE_STANDARD') == 0) {
            require_once __DIR__ . '/../../class/digiqualistandard.class.php';

            $digiqualiStandard = new DigiqualiStandard($this->db);

            $digiqualiStandard->ref         = 'ISO9001';
            $digiqualiStandard->description = $langs->transnoentities('ISO9001Description');

            $digiqualiStandardId = $digiqualiStandard->create($user);
            if ($digiqualiStandardId > 1) {
//                require_once __DIR__ . '/../../class/digiqualielement.class.php';
//
//                $digiqualiElement = new DigiqualiElement($this->db);
//
//                $digiqualiElement->ref          = 'DQE-0001';
//                $digiqualiElement->label        = $langs->transnoentities('DigiqualiElementLabelDefault');
//                $digiqualiElement->description  = $langs->transnoentities('DigiqualiElementDescriptionDefault');
//                $digiqualiElement->position     = 1;
//                $digiqualiElement->element_type = DigiqualiElement::ELEMENT_TYPE_3;
//                $digiqualiElement->fk_element   = null;
//                $digiqualiElement->fk_standard  = $digiqualiStandardId;
//
//                $digiqualiElementId = $digiqualiElement->create($user);
//                if ($digiqualiElementId > 0) {
                dolibarr_set_const($this->db, 'DIGIQUALI_ACTIVE_STANDARD', $digiqualiStandardId, 'integer', 0, '', $conf->entity);
//                } else {
//                    setEventMessages($digiqualiElement->error, $digiqualiElement->errors, 'errors');
//                    return -1;
//                }
            } else {
                setEventMessages($digiqualiStandard->error, $digiqualiStandard->errors, 'errors');
                return -1;
            }
        }

        $result = $this->initBackwardCompatibility();
        if ($result < 0) {
            setEventMessages('', $this->errors, 'errors');
            return -1;
        }

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted.
     *
     * @param  string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = ''): int
    {
        $sql = [];
        return $this->_remove($sql, $options);
    }

    public function initBackwardCompatibility(): int
    {
        if (getDolGlobalInt('DIGIQUALI_DOCUMENT_DIRECTORIES_NAME_BACKWARD_COMPATIBILITY') == 0) {
            $documentsPath = DOL_DATA_ROOT . ($conf->entity > 1 ? '/' . $conf->entity : '');
            $ecmPath =  $documentsPath . '/ecm' ;

            if (is_dir($ecmPath)) {
                if (is_dir($ecmPath . '/dolismq')) {
                    chmod($ecmPath . '/dolismq', 0755);
                    rename($ecmPath . '/dolismq', $ecmPath . '/digiquali');
                }
            }

            $moduleDocumentsPath = $documentsPath . '/dolismq';
            if (is_dir($moduleDocumentsPath)) {
                chmod($moduleDocumentsPath, 0755);
                rename($moduleDocumentsPath, $documentsPath . '/digiquali');
            }

            dolibarr_set_const($this->db, 'DIGIQUALI_DOCUMENT_DIRECTORIES_NAME_BACKWARD_COMPATIBILITY', $this->version, 'integer', 1, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIQUALI_CONTROL_BACKWARD_COMPATIBILITY') == 0) {
            require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';
            require_once __DIR__ . '/../../class/control.class.php';
            $control  = new Control($this->db);
            $controls = $control->fetchAll();
            if (is_array($controls) && !empty($controls)) {
                foreach ($controls as $control) {
                    $control->track_id = generate_random_id();
                    $control->update($user, true);

                    $url = dol_buildpath('custom/digiquali/public/control/public_control.php?track_id=' . $control->track_id . '&entity=' . $conf->entity, 3);

                    $barcode = new TCPDF2DBarcode($url, 'QRCODE,L');
                    dol_mkdir(DOL_DATA_ROOT . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'digiquali/control/' . $control->ref . '/qrcode/');
                    $file = DOL_DATA_ROOT . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'digiquali/control/' . $control->ref . '/qrcode/barcode_' . $control->track_id . '.png';

                    $imageData = $barcode->getBarcodePngData();
                    $imageData = imagecreatefromstring($imageData);
                    imagepng($imageData, $file);
                }
            }

            dolibarr_set_const($this->db, 'DIGIQUALI_CONTROL_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIQUALI_SHEET_BACKWARD_COMPATIBILITY') == 0) {
            require_once __DIR__ . '/../../class/sheet.class.php';
            $sheet  = new Sheet($this->db);
            $sheets = $sheet->fetchAll();
            if (is_array($sheets) && !empty($sheets)) {
                foreach ($sheets as $sheet) {
                    $sheet->type = 'control';
                    $sheet->setValueFrom('type', $sheet->type, '', '', 'text', '', $user, strtoupper($sheet->element) . '_MODIFY');
                }
            }

            dolibarr_set_const($this->db, 'DIGIQUALI_SHEET_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIQUALI_QUESTION_BACKWARD_COMPATIBILITY') == 0) {
            require_once __DIR__ . '/../../class/question.class.php';
            require_once __DIR__ . '/../../class/answer.class.php';

            $question  = new Question($this->db);
            $answer    = new Answer($this->db);

            $questions = $question->fetchAll('', '', 0, 0, ['customsql' => 't.type = \'OkKoToFixNonApplicable\'']);
            if (is_array($questions) && !empty($questions)) {
                foreach ($questions as $question) {
                    $answer->fk_question = $question->id;
                    $answer->value       = $langs->transnoentities('OK');
                    $answer->pictogram   = 'check';
                    $answer->color       = '#47e58e';

                    $answer->create($user);

                    $answer->fk_question = $question->id;
                    $answer->value       = $langs->transnoentities('KO');
                    $answer->pictogram   = 'times';
                    $answer->color       = '#e05353';

                    $answer->create($user);

                    $answer->fk_question = $question->id;
                    $answer->value       = $langs->transnoentities('ToFix');
                    $answer->pictogram   = 'tools';
                    $answer->color       = '#e9ad4f';

                    $answer->create($user);

                    $answer->fk_question = $question->id;
                    $answer->value       = $langs->transnoentities('NonApplicable');
                    $answer->pictogram   = 'N/A';
                    $answer->color       = '#989898';

                    $answer->create($user);
                }
            }

            dolibarr_set_const($this->db, 'DIGIQUALI_QUESTION_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIQUALI_CONTROL_ANSWER_BACKWARD') == 0) {

            require_once __DIR__ . '/../../class/control.class.php';
            require_once __DIR__ . '/../../class/sheet.class.php';

            $control    = new Control($this->db);
            $sheet      = new Sheet($this->db);
            $objectLine = new ControlLine($this->db);

            $controls = $control->fetchAll();
            if (is_array($controls) && !empty($controls)) {
                foreach ($controls as $control) {
                    if (empty($control->fk_sheet)) {
                        continue;
                    }

                    $sheet->fetch($control->fk_sheet);
                    $sheet->fetchObjectLinked($control->fk_sheet, 'digiquali_' . $sheet->element);
                    if (empty($sheet->linkedObjects['digiquali_question'])) {
                        continue;
                    }

                    $firstQuestion = current($sheet->linkedObjects['digiquali_question']);
                    $res           = $objectLine->fetchFromParentWithQuestion($control->id, $firstQuestion->id);
                    if (empty($res)) {
                        foreach ($sheet->linkedObjects['digiquali_question'] as $question) {
                            $objectLine->ref         = $objectLine->getNextNumRef();
                            $fk_element              = 'fk_'. $control->element;
                            $objectLine->$fk_element = $control->id;
                            $objectLine->fk_question = $question->id;
                            $objectLine->answer      = '';
                            $objectLine->comment     = '';
                            $objectLine->entity      = $conf->entity;
                            $objectLine->status      = 1;

                            $objectLine->create($user);
                        }
                    }
                }
            }

            dolibarr_set_const($this->db, 'DIGIQUALI_CONTROL_ANSWER_BACKWARD', 1, 'integer', 0, '', $conf->entity);
        }

        return 1;
    }
}
