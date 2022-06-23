<?php
/**
 * SieveFilters modules
 * @package modules
 * @subpackage sievefilters
 */

if (!defined('DEBUG_MODE')) { die(); }

require_once VENDOR_PATH.'autoload.php';
use PhpSieveManager\ManageSieve\Client;

/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_edit_filter extends Hm_Handler_Module {
    public function process() {
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $script = $client->getScript($this->request->post['sieve_script_name']);
        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[1]);
        $this->out('conditions', json_encode(base64_decode($base64_obj)));
        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[2]);
        $this->out('actions', json_encode(base64_decode($base64_obj)));
        if (strstr($script, 'allof')) {
            $this->out('test_type', 'ALLOF');
        } else {
            $this->out('test_type', 'ANYOF');
        }
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sieve_edit_filter extends Hm_Output_Module {
    public function output() {
        $conditions = $this->get('conditions', '');
        $this->out('conditions', $conditions);
        $actions = $this->get('actions', '');
        $this->out('actions', $actions);
        $actions = $this->get('test_type', '');
        $this->out('test_type', $actions);
    }
}

/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_edit_script extends Hm_Handler_Module {
    public function process() {
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $script = $client->getScript($this->request->post['sieve_script_name']);
        $this->out('script', $script);
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sieve_edit_output extends Hm_Output_Module {
    public function output() {
        $script = $this->get('script', '');
        $this->out('script', $script);
    }
}


/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_delete_filter extends Hm_Handler_Module {
    public function process() {
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $scripts = $client->listScripts();

        foreach ($scripts as $script) {
            if ($script == 'main_script') {
                $client->removeScripts('main_script');
            }
            if ($script == $this->request->post['sieve_script_name']) {
                $client->removeScripts($this->request->post['sieve_script_name']);
                $this->out('script_removed', true);
            }
        }
        $scripts = $client->listScripts();
        $main_script = generate_main_script($scripts);
        $client->putScript(
            'main_script',
            $main_script
        );
        $client->activateScript('main_script');
        Hm_Msgs::add('Script removed');
    }
}


/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_delete_script extends Hm_Handler_Module {
    public function process() {
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $scripts = $client->listScripts();
        foreach ($scripts as $script) {
            if ($script == 'main_script') {
                $client->removeScripts('main_script');
            }
            if ($script == $this->request->post['sieve_script_name']) {
                $client->removeScripts($this->request->post['sieve_script_name']);
                $this->out('script_removed', true);
            }
        }
        $scripts = $client->listScripts();
        $main_script = generate_main_script($scripts);

        $client->putScript(
            'main_script',
            $main_script
        );
        $client->activateScript('main_script');
        Hm_Msgs::add('Script removed');
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sieve_delete_output extends Hm_Output_Module {
    public function output() {
        $removed = $this->get('script_removed', false);
        $this->out('script_removed', $removed);
    }
}

/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_save_filter extends Hm_Handler_Module {
    public function process() {
        $priority =  $this->request->post['sieve_filter_priority'];
        if ($this->request->post['sieve_filter_priority'] == '') {
            $priority = 0;
        }
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $script_name = generate_filter_name($this->request->post['sieve_filter_name'], $priority);
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $conditions = json_decode($this->request->post['conditions_json']);
        $actions = json_decode($this->request->post['actions_json']);
        $test_type = strtolower($this->request->post['filter_test_type']);
        
        $filter = \PhpSieveManager\Filters\FilterFactory::create($script_name);
        $custom_condition = new \PhpSieveManager\Filters\Condition(
            "CYPHT GENERATED CONDITION", $test_type
        );
        foreach ($conditions as $condition) {
            $cond = null;
            if ($condition->condition == 'body') {
                $filter->addRequirement('body');
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('body');
                if ($condition->type == 'Matches') {
                    $cond->matches('"'.$condition->value.'"');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"'.$condition->value.'"');
                }
                if ($condition->type == 'Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not body');
                    $cond->matches('"'.$condition->value.'"');
                }
                if ($condition->type == 'Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not body');
                    $cond->contains('"'.$condition->value.'"');
                }
            }
            if ($condition->condition == 'subject') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('header');
                if ($condition->type == 'Matches') {
                    $cond->matches('"Subject" ["'.$condition->value.'"]');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"Subject" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->matches('"Subject" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->contains('"Subject" ["'.$condition->value.'"]');
                }
            }
            if ($condition->condition == 'to') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('header');
                if ($condition->type == 'Matches') {
                    $cond->matches('"Delivered-To" ["'.$condition->value.'"]');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"Delivered-To" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->matches('"Delivered-To" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->contains('"Delivered-To" ["'.$condition->value.'"]');
                }
            }
            if ($condition->condition == 'from') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('header');
                if ($condition->type == 'Matches') {
                    $cond->matches('"From" ["'.$condition->value.'"]');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"From" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->matches('"From" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->contains('"From" ["'.$condition->value.'"]');
                }
            }
            if ($condition->condition == 'bcc') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('header');
                if ($condition->type == 'Matches') {
                    $cond->matches('"Bcc" ["'.$condition->value.'"]');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"Bcc" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->matches('"Bcc" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->contains('"Bcc" ["'.$condition->value.'"]');
                }
            }
            if ($condition->condition == 'cc') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('header');
                if ($condition->type == 'Matches') {
                    $cond->matches('"Cc" ["'.$condition->value.'"]');
                }
                if ($condition->type == 'Contains') {
                    $cond->contains('"Cc" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Matches') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->matches('"Cc" ["'.$condition->value.'"]');
                }
                if ($condition->type == '!Contains') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not header');
                    $cond->contains('"Cc" ["'.$condition->value.'"]');
                }
            }
            if ($condition->condition == 'size') {
                $cond = \PhpSieveManager\Filters\FilterCriteria::if('size');
                if ($condition->type == 'Over') {
                    $cond->over($condition->value.'K');
                }
                if ($condition->type == 'Under') {
                    $cond->under($condition->value.'K');
                }
                if ($condition->type == '!Over') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not size');
                    $cond->over($condition->value.'K');
                }
                if ($condition->type == '!Under') {
                    $cond = \PhpSieveManager\Filters\FilterCriteria::if('not size');
                    $cond->under($condition->value.'K');
                }
            }
            if ($cond) {
                $custom_condition->addCriteria($cond);
            }
        }

        foreach ($actions as $action) {
            if ($action->action == 'discard') {
                $custom_condition->addAction(
                    new \PhpSieveManager\Filters\Actions\DiscardFilterAction()
                );
            }
            if ($action->action == 'keep') {
                $custom_condition->addAction(
                    new \PhpSieveManager\Filters\Actions\KeepFilterAction()
                );
            }
            if ($action->action == 'redirect') {
                $custom_condition->addAction(
                    new \PhpSieveManager\Filters\Actions\RedirectFilterAction([$action->value])
                );
            }
            if ($action->action == 'flag') {
                $custom_condition->addAction(
                    new \PhpSieveManager\Filters\Actions\FlagFilterAction([$action->value])
                );
            }
            if ($action->action == 'copy') {
                $filter->addRequirement('fileinto');
                $custom_condition->addAction(
                    new \PhpSieveManager\Filters\Actions\FileIntoFilterAction([$action->value])
                );
            }
        }
        $filter->setCondition($custom_condition);
        $script_parsed = $filter->toScript();

        $header_obj = "# CYPHT CONFIG HEADER - DON'T REMOVE";
        $header_obj .= "\n# ".base64_encode($this->request->post['conditions_json']);
        $header_obj .= "\n# ".base64_encode($this->request->post['actions_json']);
        $script_parsed = $header_obj."\n\n".$script_parsed;

        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $scripts = $client->listScripts();
        foreach ($scripts as $script) {
            if ($script == 'main_script') {
                $client->removeScripts('main_script');
            }
            if ($script == $this->request->post['current_editing_filter_name']) {
                $client->removeScripts($this->request->post['current_editing_filter_name']);
            }
        }

        $client->putScript(
            $script_name,
            $script_parsed
        );

        $scripts = $client->listScripts();
        $main_script = generate_main_script($scripts);

        $client->putScript(
            'main_script',
            $main_script
        );
        $client->activateScript('main_script');
    }
}

/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_sieve_save_script extends Hm_Handler_Module {
    public function process() {
        $priority =  $this->request->post['sieve_script_priority'];
        if ($this->request->post['sieve_script_priority'] == '') {
            $priority = 0;
        }
        $script_name = generate_script_name($this->request->post['sieve_script_name'], $priority);
        foreach ($this->user_config->get('imap_servers') as $mailbox) {
            if ($mailbox['name'] == $this->request->post['imap_account']) {
                $imap_account = $mailbox;
            }
        }
        $sieve_options = explode(':', $imap_account['sieve_config_host']);
        $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
        $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
        $scripts = $client->listScripts();
        foreach ($scripts as $script) {
            if ($script == $this->request->post['current_editing_script']) {
                $client->removeScripts($this->request->post['current_editing_script']);
            }
        }
        $client->putScript(
            $script_name,
            $this->request->post['script']
        );
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sieve_save_script_output extends Hm_Output_Module {
    public function output() {

    }
}

/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_settings_load_imap extends Hm_Handler_Module {
    public function process() {
        $this->out('imap_accounts', $this->user_config->get('imap_servers'), array());
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sievefilters_settings_link extends Hm_Output_Module {
    protected function output() {
        $res = '<li class="menu_profiles"><a class="unread_link" href="?page=sievefilters">';
        if (!$this->get('hide_folder_icons')) {
            $res .= '<img class="account_icon" src="'.$this->html_safe(Hm_Image_Sources::$book).'" alt="" width="16" height="16" /> ';
        }
        $res .= $this->trans('Filters').'</a></li>';
        if ($this->format == 'HTML5') {
            return $res;
        }
        $this->concat('formatted_folder_list', $res);
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sievefilters_settings_start extends Hm_Output_Module {
    protected function output() {
        $socked_connected = $this->get('socket_connected', false);

        $res = '<div class="sievefilters_settings"><div class="content_title">'.$this->trans('Filters').'</div>';
        $res .= '<script type="text/css" src="'.WEB_ROOT.'modules/sievefilters/assets/tingle.min.css"></script>';
        return $res;
    }
}

/**
 * @subpackage sievefilters/output
 */
class Hm_Output_sievefilters_settings_accounts extends Hm_Output_Module {
    protected function output() {
        $mailboxes = $this->get('imap_accounts', array());
        $res = get_classic_filter_modal_content();
        $res .= get_script_modal_content();
        foreach($mailboxes as $mailbox) {
            if (isset($mailbox['sieve_config_username'])) {
                $num_filters = sizeof(get_mailbox_filters($mailbox));
                $res .= '<div class="sievefilters_accounts_item">';
                $res .= '<div class="sievefilters_accounts_title settings_subtitle">' . $mailbox['name'];
                $res .= '<span class="filters_count">' . sprintf($this->trans('%s filters'), $num_filters) . '</span></div>';
                $res .= '<div class="sievefilters_accounts filter_block"><div class="filter_subblock">';
                $res .= '<button class="add_filter" account="'.$mailbox['name'].'">Add Filter</button> <button  account="'.$mailbox['name'].'" class="add_script">Add Script</button>';
                $res .= '<table class="filter_details"><tbody>';
                $res .= '<tr><th style="width: 80px;">Priority</th><th>Name</th><th style="width: 15%;">Actions</th></tr>';
                $res .= get_mailbox_filters($mailbox, true);
                $res .= '</tbody></table>';
                $res .= '<div style="height: 40px; margin-bottom: 10px; display: none;">
                                <div style="width: 90%;">
                                    <h3 style="margin-bottom: 2px;">If conditions are not met</h3>
                                    <small>Define the actions if conditions are not met. If no actions are provided the next filter will be executed. If there are no other filters to be executed, the email will be delivered as expected.</small>
                                </div>
                            </div>
                            <div style="background-color: #f7f2ef; margin-top: 25px; width: 90%; display: none;">
                                <div style="padding: 10px;">
                                    <div style="display: flex; height: 30px;">
                                        <div style="width: 80%;">
                                            <h5 style="margin-top: 0">Actions</h5>
                                        </div>
                                        <div style="flex-grow: 1; text-align: right;">
                                            <button style="margin-right: 10px;" class="filter_modal_add_else_action_btn">Add Action</button>
                                        </div>
                                    </div>
                                    <div style="width: 100%;">
                                        <table class="filter_else_actions_modal_table">
                                        </table>
                                    </div>
                                </div>
                            </div>';
                $res .= '</div></div></div>';
            }
        }
        return $res;
    }
}

if (!hm_exists('get_script_modal_content')) {
    function get_script_modal_content()
    {
        return '<div id="edit_script_modal" style="display: none;">
            <h1 class="script_modal_title"></h1>  
            <hr/>
            <div style="display: flex; height: 70px; margin-bottom: 10px;">
                <div style="width: 100%;">
                    <h3 style="margin-bottom: 2px;">General</h3>
                    <small>Input a name and order for your filter. In filters, the order of execution is important. You can define an order value (or priority value) for your filter. Filters will run from lowest to highest priority value.</small>
                </div>
            </div>
            <div style="margin-bottom: 10px; margin-top: 25px;">
                <b>Filter Name:</b><input class="modal_sieve_script_name" type="text" placeholder="Your filter name" style="margin-left: 10px;" /> 
                <b style="margin-left: 50px;">Priority:</b><input class="modal_sieve_script_priority" type="number" placeholder="0" style="margin-left: 10px;" /> 
            </div>
            <div style="display: flex; height: 70px; margin-bottom: 10px;">
                <div style="width: 100%;">
                    <h3 style="margin-bottom: 2px;">Sieve Script</h3>
                    <small>Paste the Sieve script in the field below. Manually added scripts cannot be edited with the filters interface.</small>
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <textarea style="width: 100%;" rows="30" class="modal_sieve_script_textarea"></textarea>
            </div>
        </div>';
    }
}


if (!hm_exists('get_classic_filter_modal_content')) {
    function get_classic_filter_modal_content()
    {
            return '<div id="edit_filter_modal" style="display: none;">
            <h1 class="filter_modal_title"></h1>  
            <hr/>
            <div style="display: flex; height: 70px; margin-bottom: 10px;">
                <div style="width: 100%;">
                    <h3 style="margin-bottom: 2px;">General</h3>
                    <small>Input a name and order for your filter. In filters, the order of execution is important. You can define an order value (or priority value) for your filter. Filters will run from lowest to highest priority value.</small>
                </div>
            </div>
            <div style="margin-bottom: 10px; margin-top: 25px;">
                <b>Filter Name:</b><input type="text" class="modal_sieve_filter_name" placeholder="Your filter name" style="margin-left: 10px;" /> 
                <b style="margin-left: 20px;">Priority:</b><input class="modal_sieve_filter_priority" type="number" placeholder="0" style="margin-left: 10px;" /> 
                <b style="margin-left: 20px;">Test:</b>
                    <select class="modal_sieve_filter_test" name="test_type" placeholder="0" style="margin-left: 10px;"> 
                        <option value="ANYOF">ANYOF (OR)</option>
                        <option value="ALLOF">ALLOF (AND)</option>
                    </select>
            </div>
            <div style="display: flex; height: 70px; margin-bottom: 10px;">
                <div style="width: 100%;">
                    <h3 style="margin-bottom: 2px;">Conditions & Actions</h3>
                    <small>Filters must have at least one action and one condition</small>
                </div>
            </div>
            <div style="background-color: #f7f2ef; margin-top: 10px;">
                <div style="padding: 10px;">
                    <div style="display: flex; height: 30px;">
                        <div style="width: 80%;">
                            <h5 style="margin-top: 0">Conditions</h5>
                        </div>
                        <div style="flex-grow: 1; text-align: right;">
                            <button style="margin-right: 10px;" class="sieve_add_condition_modal_button">Add Condition</button>
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <table class="sieve_list_conditions_modal">
                        </table>
                    </div>
                </div>
                <hr/>
                <div style="padding: 10px;">
                    <div style="display: flex; height: 30px;">
                        <div style="width: 80%;">
                            <h5 style="margin-top: 0">Actions</h5>
                        </div>
                        <div style="flex-grow: 1; text-align: right;">
                            <button style="margin-right: 10px;" class="filter_modal_add_action_btn">Add Action</button>
                        </div>
                    </div>
                    <div style="width: 100%;">
                        <table class="filter_actions_modal_table">
                        </table>
                    </div>
                </div>
            </div>
        </div>';
    }
}

if (!hm_exists('get_mailbox_filters')) {
    function get_mailbox_filters($mailbox, $html=false)
    {
        try {
            $sieve_options = explode(':', $mailbox['sieve_config_host']);
            $client = new \PhpSieveManager\ManageSieve\Client($sieve_options[0], $sieve_options[1]);
            $client->connect($mailbox['sieve_config_username'], $mailbox['sieve_config_password'], false, "", "PLAIN");
            $scripts = [];
            foreach ($client->listScripts() as $script) {
                if ($script != 'main_script') {
                    $scripts[] = $script;
                }
            }
        } catch (PhpSieveManager\Exceptions\SocketException $e) {
            return '';
        }

        if ($html == false) {
            return $scripts;
        }

        $scripts_sorted = [];
        foreach ($scripts as $script_name) {
            $exp_name = explode('-', $script_name);
            if (end($exp_name) == 'cypht') {
                $base_class = 'script';
            }
            else if (end($exp_name) == 'cyphtfilter') {
                $base_class = 'filter';
            }
            else {
                continue;
            }
            $parsed_name = str_replace('_', ' ', $exp_name[0]);
            $scripts_sorted[$script_name] = $exp_name[sizeof($exp_name) - 2];
        }
        asort($scripts_sorted);

        $script_list = '';
        foreach ($scripts_sorted as $script_name => $sc) {
            $exp_name = explode('-', $script_name);
            $script_list .= '
            <tr>
                <td>'. $exp_name[sizeof($exp_name) - 2] .'</td>
                <td>' . str_replace('_', ' ', $exp_name[sizeof($exp_name) - 3]) . '</td>
                <td>
                    <a href="#" script_name_parsed="'.$parsed_name.'"  priority="'.$exp_name[sizeof($exp_name) - 2].'" imap_account="'.$mailbox['name'].'" script_name="'.$script_name.'"  class="edit_'.$base_class.'">
                        <img width="16" height="16" src="' . Hm_Image_Sources::$edit . '" />
                    </a>
                    <a href="#" script_name_parsed="'.$parsed_name.'" priority="'.$exp_name[sizeof($exp_name) - 2].'" imap_account="'.$mailbox['name'].'" style="padding-left: 5px;" script_name="'.$script_name.'" class="delete_'.$base_class.'">
                        <img width="16" height="16" src="' . Hm_Image_Sources::$minus . '" />
                    </a>
                </td>
            </tr>
            ';
        }
        return $script_list;
    }
}

if (!hm_exists('generate_main_script')) {
    function generate_main_script($script_list)
    {
        $sorted_list = [];
        foreach ($script_list as $script_name) {
            if ($script_name == 'main_script') {
                continue;
            }

            if (strstr($script_name, 'cypht')) {
                $ex_name = explode('-', $script_name);
                $sorted_list[$script_name] = $ex_name[1];
            }
        }
        asort($sorted_list);
        $include_header = 'require ["include"];'."\n\n";
        $include_body = '';
        foreach ($parsed_list as $include_script) {
            $include_body .= 'include :personal "'.$include_script['name'].'";'."\n";
        }
        return $include_header.$include_body;
    }
}

if (!hm_exists('generate_script_name')) {
    function generate_script_name($name, $priority)
    {
        return str_replace(' ', '_', strtolower($name)).'-'.$priority.'-cypht';
    }
}

if (!hm_exists('generate_filter_name')) {
    function generate_filter_name($name, $priority)
    {
        return str_replace(' ', '_', strtolower($name)).'-'.$priority.'-cyphtfilter';
    }
}
