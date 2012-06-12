<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Tracker_DateReminder.class.php');
require_once('dao/Tracker_DateReminderDao.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php');
require_once('common/mail/MailManager.class.php');
require_once 'common/date/DateHelper.class.php';

class Tracker_DateReminderManager {

    protected $tracker;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker(){
        return $this->tracker;
    }

    /**
     * Process nightly job to send reminders
     *
     * @return Void
     */
    public function process() {
        $reminders = $this->getTrackerReminders();
        foreach ($reminders as $reminder) {
            
            $artifacts = $this->getArtifactsByreminder($reminder);
            foreach ($artifacts as $artifact) {
                $this->sendReminderNotification($reminder, $artifact);
            }
        }
    }

    /**
     * Send reminder
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact Artifact for which reminders will be sent
     *
     * @return Void
     */
    protected function sendReminderNotification(Tracker_DateReminder $reminder, Tracker_Artifact $artifact) {
        $tracker    = $this->getTracker();
        // 1. Get the recipients list
        $recipients = $reminder->getRecipients();

        // 2. Compute the body of the message + headers
        $messages   = array();
        foreach ($recipients as $recipient) {
            if ($recipient && $artifact->userCanView($recipient) && $reminder->getField()->userCanRead($recipient)) {
                $this->buildMessage($reminder, $artifact, $messages, $recipient);
            }
        }

        // 3. Send the notification
        foreach ($messages as $m) {
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_sent", $this->tracker->getName().":".$reminder->getField()->getId(), $this->tracker->getGroupId(), $m['recipients']);
            $this->sendReminder($artifact, $m['recipients'], $m['headers'], $m['subject'], $m['htmlBody'], $m['txtBody']);
        }
    }

    /**
     * Build the reminder messages
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact Artifact for which reminders will be sent
     * @param Array            $messages Messages
     * @param User             $user     Receipient
     *
     * return Array
     */
    protected function buildMessage(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, &$messages, $user) {
        $mailManager = new MailManager();

        $recipient = $user->getEmail();
        $lang      = $user->getLanguage();
        $format    = $mailManager->getMailPreferencesByUser($user);

        //We send multipart mail: html & text body in case of preferences set to html
        $htmlBody  = '';
        if ($format == Codendi_Mail_Interface::FORMAT_HTML) {
            //$htmlBody  .= $this->getBodyHtml($reminder, $user, $lang);
        }
        $txtBody   = $this->getBodyText($reminder, $artifact, $user, $lang);

        $subject   = $this->getSubject($reminder, $artifact, $user);
        $headers   = array(); 
        $hash      = md5($htmlBody . $txtBody . serialize($headers) . serialize($subject));
        if (isset($messages[$hash])) {
            $messages[$hash]['recipients'][] = $recipient;
        } else {
            $messages[$hash] = array(
                    'headers'    => $headers,
                    'htmlBody'   => $htmlBody,
                    'txtBody'    => $txtBody,
                    'subject'    => $subject,
                    'recipients' => array($recipient),
            );
        }
    }
    
    /**
     * Send a notification
     *
     * @param Array  $recipients the list of recipients
     * @param Array  $headers    the additional headers
     * @param String $subject    the subject of the message
     * @param String $htmlBody   the html content of the message
     * @param String $txtBody    the text content of the message
     *
     * @return Void
     */
    protected function sendReminder(Tracker_Artifact $artifact, $recipients, $headers, $subject, $htmlBody, $txtBody) {
        $mail          = new Codendi_Mail();
        $hp            = Codendi_HTMLPurifier::instance();
        $breadcrumbs   = array();
        $groupId       = $this->getTracker()->getGroupId();
        $project       = $this->getTracker()->getProject();
        $trackerId     = $this->getTracker()->getID();
        $artifactId    = $artifact->getID();

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url() .'/plugins/tracker/?tracker='. (int)$trackerId .'" />'. $hp->purify(SimpleSanitizer::unsanitize($this->getTracker()->getName())) .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url().'/plugins/tracker/?aid='.(int)$artifactId.'" />'. $hp->purify($this->getTracker()->getName().' #'.$artifactId) .'</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->getLookAndFeelTemplate()->set('title', $hp->purify($subject));
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->addAdditionalHeader("X-Codendi-Project",     $this->getTracker()->getProject()->getUnixName());
        $mail->addAdditionalHeader("X-Codendi-Tracker",     $this->getTracker()->getItemName());
        $mail->addAdditionalHeader("X-Codendi-Artifact-ID", $artifact->getId());
        foreach($headers as $header) {
            $mail->addAdditionalHeader($header['name'], $header['value']);
        }
        $mail->setTo(implode(', ', $recipients));
        $mail->setSubject($subject);
        if ($htmlBody) {
            $mail->setBodyHTML($htmlBody);
        }
        $mail->setBodyText($txtBody);
        $mail->send();
    }

    /**
     * Get the subject for reminder
     *
     * @param String $recipient The recipient who will receive the reminder
     *
     * @return String
     */
    public function getSubject($reminder, $artifact, $recipient) {
        $s = "[" . $this->tracker->getName()."] ".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','subject', array($reminder->getField()->getLabel(), $artifact->getValue($reminder->getField())->getValue(), $artifact->getTitle()));
        return $s;
    }

    /**
     * Get the text body for notification
     *
     * @param Tracker_DateReminder $reminder     Reminder that will send notifications
     * @param Tracker_Artifact     $artifact     ???
     * @param String               $recipient    The recipient who will receive the notification
     * @param BaseLanguage         $language     The language of the message
     *
     * @return String
     */
    protected function getBodyText(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, $recipient, BaseLanguage $language) {
        $format = Codendi_Mail_Interface::FORMAT_TEXT;
        $proto  = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link   = ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'>';

        $output = '+============== '.'['.$this->getTracker()->getItemName() .' #'. $artifact->getId().'] '.$artifact->fetchMailTitle($recipient, $format, false).' ==============+';
        $output .= PHP_EOL;

        $output = "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_header',array($GLOBALS['sys_name'], $reminder->getField()->getLabel(), $artifact->getValue($reminder->getField())->getValue())).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_project',array($this->getTracker()->getProject()->getPublicName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_tracker',array($this->getTracker()->getName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art',array($artifact->getTitle())).
            "\n".$reminder->getField()->getLabel().": ".$artifact->getValue($reminder->getField())->getValue().
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art_link').
            "\n".$link."\n";
        return $output;
    }

    /**
     * Get the html body for notification
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact
     * @param String  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     *
     * @return String
     */
    protected function getBodyHtml(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, $recipient, BaseLanguage $language) {
        $format = Codendi_Mail_Interface::FORMAT_HTML;
        $proto  = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link   .= ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'>';

        $output ='<h1>'.$hp->purify($art->fetchMailTitle($recipient, $format, false)).'</h1>'.PHP_EOL;

        $output = "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_header',array($GLOBALS['sys_name'], $reminder->getField()->getLabel(), $artifact->getValue($reminder->getField())->getValue())).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_project',array($this->getTracker()->getProject()->getPublicName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_tracker',array($this->getTracker()->getName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art',array($artifact->getTitle())).
            "\n".$reminder->getField()->getLabel().": ".$artifact->getValue($reminder->getField())->getValue().
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art_link').
            "\n".$link."\n";
        return $output;
    }

    /**
     * Retrieve all date reminders for a given tracker
     *
     * @return Array
     */
    public function getTrackerReminders() {
        $reminders          = array();
        $reminderManagerDao = $this->getDao();
        $dar = $reminderManagerDao->getDateReminders($this->tracker->getId());
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $reminders[] = $this->getInstanceFromRow($row);
            }
        }
        return $reminders;
    }

    /**
     * Build a reminder instance
     *
     * @param array $row The data describing the reminder
     *
     * @return Tracker_DateReminder
     */
    public function getInstanceFromRow($row) {
        return new Tracker_DateReminder($row['reminder_id'],
                                          $row['tracker_id'],
                                          $row['field_id'],
                                          $row['ugroups'],
                                          $row['notification_type'],
                                          $row['distance'],
                                          $row['status']);
    }

    /**
     * Get the Tracker_DateReminder dao
     *
     * @return Tracker_DateReminderDao
     */
    protected function getDao() {
        return new Tracker_DateReminderDao();
    }

    /** Get artifacts that will send notification for a reminder
     *
     * @param Tracker_DateReminder $reminder Reminder on which the notification is based on
     *
     * @return Array
     */
    public function getArtifactsByreminder(Tracker_DateReminder $reminder) {
        $artifacts = array();
        $date      = DateHelper::getDistantDateFromToday($reminder->getDistance(), $reminder->getNotificationType());
        $field     = $reminder->getField();
        if ($field instanceof Tracker_FormElement_Field_LastUpdateDate) {
            $dao = new Tracker_Artifact_ChangesetDao();
            $dar = $dao->getArtifactsByFieldAndLastUpdateDate($this->getTracker()->getId(), $date);
        } elseif ($field instanceof Tracker_FormElement_Field_SubmittedOn) {
            $dao = new Tracker_ArtifactDao();
            $dar = $dao->getArtifactsBySubmittedOnDate($this->getTracker()->getId(), $date);
        } elseif ($field instanceof Tracker_FormElement_Field_Date) {
            $dao = new Tracker_FormElement_Field_Value_DateDao();
            $dar = $dao->getArtifactsByFieldAndValue($reminder->getFieldId(), $date);
        }
        if ($dar && !$dar->isError()) {
            $artifactFactory = Tracker_ArtifactFactory::instance();
            foreach ($dar as $row) {
                $artifacts[] = $artifactFactory->getArtifactById($row['artifact_id']);
            }
        }
        return $artifacts;
    }
}

?>