<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Scheduler_Controller
{

    /**
     * @var SchedulersLib
     */
    private $lib;

    /**
     * @var TikiAccessLib
     */
    private $access;

    public function setUp()
    {
        $this->lib = TikiLib::lib('scheduler');
        $this->access = TikiLib::lib('access');
    }


    /**
     * Admin user "perform with checked" action to remove selected users
     *
     * @param $input JitFilter
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_remove($input)
    {
        Services_Exception_Denied::checkGlobal('admin_users');

        $schedulerId = $input->schedulerId->int();

        $scheduler = $this->lib->get_scheduler($schedulerId);

        if (! $scheduler) {
            throw new Services_Exception_NotFound;
        }
        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $this->lib->remove_scheduler($schedulerId);

            return [
                'schedulerId' => 0,
            ];
        }

        return [
            'schedulerId' => $schedulerId,
            'name' => $scheduler['name'],
        ];
    }

    /**
     * Execute one scheduler from the admin interface
     *
     * @param $input
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_run($input)
    {
        global $user;

        Services_Exception_Denied::checkGlobal('admin_users');

        $schedulerId = $input->schedulerId->int();

        $scheduler = $this->lib->get_scheduler($schedulerId);

        if (! $scheduler) {
            throw new Services_Exception_NotFound;
        }

        $logger = new Tiki_Log('Webcron', \Psr\Log\LogLevel::ERROR);
        $schedulerTask = Scheduler_Item::fromArray($scheduler, $logger);

        $message = tr('Execution output:') . '<br><br>';

        // Prevent feedback collection during scheduler run from UI
        $feedback = isset($_SESSION['tikifeedback']) ? $_SESSION['tikifeedback'] : [];

        $result = $schedulerTask->execute($user);

        // Remove feedback collected during the scheduler process
        $_SESSION['tikifeedback'] = $feedback;

        if ($result['status'] == 'failed') {
            $message .= tr('Scheduler %0 - FAILED', $schedulerTask->name) . '<br>' . $result['message'];
        } else {
            $message .= tr('Scheduler %0 - OK', $schedulerTask->name) . '<br>';
            $message .= $result['message'];
        }

        $message = str_replace(PHP_EOL, '<br>', $message);

        return [
            'title' => tr('Running %0', $schedulerTask->name),
            'schedulerId' => $schedulerId,
            'name' => $scheduler['name'],
            'message' => $message,
        ];
    }

    /**
     * Execute scheduler in the next time the “scheduler” runs
     *
     * @param $input
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_run_background($input)
    {
        global $user;

        Services_Exception_Denied::checkGlobal('admin_users');

        $schedulerId = $input->schedulerId->int();

        $scheduler = $this->lib->get_scheduler($schedulerId);
        if (! $scheduler) {
            throw new Services_Exception_NotFound;
        }

        $schedulerId = $this->lib->set_scheduler(
            $scheduler['name'],
            $scheduler['description'],
            $scheduler['task'],
            $scheduler['params'],
            $scheduler['run_time'],
            $scheduler['status'],
            $scheduler['re_run'],
            $scheduler['run_only_once'],
            $scheduler['id'],
            null,
            $user
        );

        return [
            'title' => tr('Scheduler %0 will be executed on next cron run', $scheduler['name']),
            'schedulerId' => $schedulerId,
        ];
    }

    /**
     * Reset a running scheduler (if it's stucked for a unknown reason)
     *
     * @param $input
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     * @return array
     */
    public function action_reset($input)
    {
        global $user;

        Services_Exception_Denied::checkGlobal('admin_users');

        $schedulerId = $input->schedulerId->int();

        $scheduler = $this->lib->get_scheduler($schedulerId);
        if (! $scheduler) {
            throw new Services_Exception_NotFound;
        }

        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $logger = new Tiki_Log('Webcron', \Psr\Log\LogLevel::ERROR);
            $item = Scheduler_Item::fromArray($scheduler, $logger);
            $item->heal('Reset by ' . $user, false, true);

            return [
                'schedulerId' => 0
            ];
        }

        return [
            'title' => tr('Reset scheduler run?'),
            'schedulerId' => $schedulerId,
        ];
    }
}
