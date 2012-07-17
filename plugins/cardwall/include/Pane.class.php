<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

require_once 'common/TreeNode/TreeNodeMapper.class.php';
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';
require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'BoardFactory.class.php';
require_once 'PaneContentPresenter.class.php';
require_once 'QrCode.class.php';
require_once 'CreateCardPresenterCallback.class.php';
require_once 'CardInCellPresenterCallback.class.php';

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane {

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var bool
     */
    private $enable_qr_code;
    
    /** @var Cardwall_OnTop_Config */
    private $config;

    public function __construct(Planning_Milestone $milestone, $enable_qr_code, Cardwall_OnTop_Config $config) {
        $this->milestone      = $milestone;
        $this->enable_qr_code = $enable_qr_code;
        $this->config         = $config;
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'cardwall';
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    public function getTitle() {
        return 'Card Wall';
    }

    /**
     * @see AgileDashboard_Pane::getContent()
     */
    public function getContent() {
        $tracker = $this->milestone->getArtifact()->getTracker();
        $columns = $this->config->getDashboardColumns();
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        return $renderer->renderToString('agiledashboard-pane', $this->getPresenterUsingMappedFields($columns));
        // TODO what if no semantic status and no mapping????
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenterUsingMappedFields(Cardwall_OnTop_Config_ColumnCollection $columns) {
        $board_factory      = new Cardwall_BoardFactory();
        $planned_artifacts  = $this->milestone->getPlannedArtifacts();

        $field_retriever    = new Cardwall_OnTop_Config_MappedFieldProvider($this->config, 
                                new Cardwall_FieldProviders_SemanticStatusFieldRetriever());
        
        $board              = $board_factory->getBoard($field_retriever, $columns, $planned_artifacts, $this->config);
        $backlog_title      = $this->milestone->getPlanning()->getBacklogTracker()->getName();
        $redirect_parameter = 'cardwall[agile]['. $this->milestone->getPlanning()->getId() .']='. $this->milestone->getArtifactId();
        $configure_url      = TRACKER_BASE_URL .'/?tracker='. $this->milestone->getTrackerId() .'&func=admin-cardwall';

        return new Cardwall_PaneContentPresenter($board, $this->getQrCode(), $redirect_parameter, $backlog_title, $configure_url);
    }

    /**
     * @return Cardwall_QrCode
     */
    private function getQrCode() {
        if ($this->enable_qr_code) {
            return new Cardwall_QrCode($_SERVER['REQUEST_URI'] .'&pv=2');
        }
        return false;
    }
}
?>
