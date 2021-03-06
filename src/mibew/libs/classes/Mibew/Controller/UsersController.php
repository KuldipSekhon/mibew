<?php
/*
 * This file is a part of Mibew Messenger.
 *
 * Copyright 2005-2014 the original author or authors.
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

namespace Mibew\Controller;

use Mibew\Settings;
use Mibew\Style\ChatStyle;
use Mibew\RequestProcessor\UsersProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains actions for all awaiting users-related functionality.
 */
class UsersController extends AbstractController
{
    /**
     * Generates a page with awaiting visitors.
     *
     * @param Request $request
     * @return string Rendered page content
     */
    public function indexAction(Request $request)
    {
        $operator = $this->getOperator();

        // Operator becomes online as soon as he open "operator/users" page
        notify_operator_alive($operator['operatorid'], 0);
        $operator['istatus'] = 0;
        $this->getAuthenticationManager()->setOperator($operator);

        $_SESSION[SESSION_PREFIX . "operatorgroups"] = get_operator_groups_list($operator['operatorid']);

        $page = array();
        $page['havemenu'] = !$request->query->has('nomenu');
        $page['showpopup'] = (Settings::get('enablepopupnotification') == '1') ? "1" : "0";
        $page['frequency'] = Settings::get('updatefrequency_operator');
        $page['showonline'] = (Settings::get('showonlineoperators') == '1') ? "1" : "0";
        $page['showvisitors'] = (Settings::get('enabletracking') == '1') ? "1" : "0";
        $page['agentId'] = $operator['operatorid'];
        $page['geoLink'] = Settings::get('geolink');
        $page['geoWindowParams'] = Settings::get('geolinkparams');
        $page['trackedLink'] = $request->getBaseUrl() . '/operator/history/user-track';
        $page['banLink'] = $request->getBaseUrl() . '/operator/ban';
        $page['inviteLink'] = $request->getBaseUrl() . '/operator/invite';
        $page['agentLink'] = $request->getBaseUrl() . '/operator/chat';

        $page['mibewBasePath'] = $request->getBasePath();
        $page['mibewBaseUrl'] = $request->getBaseUrl();

        // Load dialogs style options
        $chat_style = new ChatStyle(ChatStyle::getCurrentStyle());
        $style_config = $chat_style->getConfigurations();
        $page['chatStyles.chatWindowParams'] = $style_config['chat']['window_params'];
        $page['coreStyles.inviteWindowParams'] = $style_config['chat']['window_params'];

        // Load page style options
        $style_config = $this->getStyle()->getConfigurations();
        $page['coreStyles.threadTag'] = $style_config['users']['thread_tag'];
        $page['coreStyles.visitorTag'] = $style_config['users']['visitor_tag'];
        $page['coreStyles.trackedUserWindowParams'] = $style_config['tracked']['user_window_params'];
        $page['coreStyles.trackedVisitorWindowParams'] = $style_config['tracked']['visitor_window_params'];
        $page['coreStyles.banWindowParams'] = $style_config['ban']['window_params'];

        $page['title'] = getlocal("List of visitors waiting");
        $page['menuid'] = "users";

        // Get additional plugins data
        $page = array_merge($page, get_plugins_data($request));
        $page = array_merge($page, prepare_menu($operator));

        return $this->render('users', $page);
    }

    /**
     * Provides a gateway for client side application at awaiting visitors page.
     *
     * @param Request $request
     * @return string Rendered page content
     */
    public function updateAction(Request $request)
    {
        $processor = UsersProcessor::getInstance();
        $processor->setAuthenticationManager($this->getAuthenticationManager());

        return $processor->handleRequest($request);
    }
}
