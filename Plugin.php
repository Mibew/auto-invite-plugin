<?php
/*
 * Copyright 2018 the original author or authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Mibew\Mibew\Plugin\AutoInvite;

use Mibew\Database;
use Mibew\EventDispatcher\EventDispatcher;
use Mibew\EventDispatcher\Events;
use Mibew\Thread;

/**
 * Provides an ability to automatically invite a visitor into chat.
 */
class Plugin extends \Mibew\Plugin\AbstractPlugin implements \Mibew\Plugin\PluginInterface
{
    protected $initialized = true;

    // Time to wait before invitation
    protected $wait_time = 60;
    // Invitation strategy (i.e. how to choose an operator to send the invitation)
    protected $strategy = 'first';
    // Operators' group to send the invitation
    protected $group = 0;

    /**
     * Class constructor.
     *
     * @param array $config List of the plugin config.
     *
     */
    public function __construct($config)
    {
        if (isset($config['wait_time']) && ((int)$config['wait_time'] > 0)) {
            $this->wait_time = (int)$config['wait_time'];
        }
        if (isset($config['strategy']) && ($config['strategy'] == 'random')) {
            $this->strategy = $config['strategy'];
        }
        if (isset($config['group']) && ((int)$config['group'] > 0)) {
            $this->group = (int)$config['group'];
        }
    }

    /**
     * Defines necessary event listeners.
     */
    public function run()
    {
        $dispatcher = EventDispatcher::getInstance();
        $dispatcher->attachListener('visitorTrack', $this, 'inviteVisitor');
        $dispatcher->attachListener('invitationAccept', $this, 'markThreadAsOrphaned');
        $dispatcher->attachListener('invitationReject', $this, 'forgetThread');
        $dispatcher->attachListener('invitationIgnore', $this, 'forgetThread');
    }

    /**
     * Returns verision of the plugin.
     *
     * @return string Plugin's version.
     */
    public static function getVersion()
    {
        return '0.1.1';
    }

    /**
     * Automatically invite a visitor into chat.
     *
     * @param array $args Event data
     */
    public function inviteVisitor($args)
    {
        // Get a visitor
        $visitor = $args['visitor'];
        $visitor_id = $visitor['visitorid'];

        // Is a visitor invited at the moment?
        $invitation_state = invitation_state($visitor_id);
        // Was a visitor invited before?
        $was_invited = $visitor['invitations'] > 0;
        // How long a visitor is on the site?
        $spent_on_site = (time() - $visitor['firsttime']);
        // Are there any operators available?
        $anybody_online = has_online_operators($this->group);

        // Check whether the visitor should be invited into chat
        $send_invitation = !$invitation_state['invited']
            && !$was_invited
            && ($spent_on_site > $this->wait_time)
            && $anybody_online;

        if ($send_invitation) {
            // Determine operator to send the invitation
            $operators = get_online_operators($this->group);
            if ($this->strategy == 'first') {
                // Invite on behalf of the first available operator
                $operator = $operators[0];
            }
            else {
                // Invite on behalf of a random available operator
                $operator = $operators[array_rand($operators)];
            }
            // Invite into chat
            $thread = invitation_invite($visitor_id, $operator);

            // Store ID of the thread in the database for future use
            Database::getInstance()->query(
                'INSERT INTO {mibew_autoinvite} (threadid) VALUES (:threadid)',
                array(':threadid' => $thread->id)
            );
        }
    }

    /**
     * Mark the thread tied to invitation as orphaned (i.e. 'waiting for operator').
     *
     * @param array $args Event data
     */
    public function markThreadAsOrphaned($args)
    {
        // Get the thread related to invitation
        $thread = $args['invitation'];

        // Check whether the thread is related to automatically sent invitation
        $result = Database::getInstance()->query(
            "SELECT COUNT(*) AS autoinvited FROM {mibew_autoinvite} WHERE threadid = :threadid",
            array(':threadid' => $thread->id),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        if ($result && isset($result['autoinvited']) && ($result['autoinvited'] > 0)) {
            // A visitor was invited automatically, change thread state
            $thread->state = Thread::STATE_WAITING;
            $thread->nextAgent = 0;
            $thread->save(true);
            // Forget about the thread
            $this->forgetThread($args);
        }
    }

    /**
     * Remove ID of the thread tied to invitation from the database.
     *
     * @param array $args Event data
     */
    public function forgetThread($args)
    {
        $thread = $args['invitation'];
        Database::getInstance()->query(
            'DELETE FROM {mibew_autoinvite} WHERE threadid = :threadid',
            array(':threadid' => $thread->id)
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function install()
    {
        return Database::getInstance()->query(
            'CREATE TABLE {mibew_autoinvite} ( '
                . 'threadid INT NOT NULL PRIMARY KEY'
            . ') charset utf8 ENGINE=InnoDb'
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function uninstall()
    {
        return Database::getInstance()->query('DROP TABLE {mibew_autoinvite}');
    }
}
