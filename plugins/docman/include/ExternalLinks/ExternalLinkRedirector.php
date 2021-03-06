<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\ExternalLinks;

use Tuleap\Event\Dispatchable;

class ExternalLinkRedirector implements Dispatchable
{
    const NAME = 'externalLinkRedirector';

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Project
     */
    private $project;

    /**
     * @var \HTTPRequest
     */
    private $request;

    /**
     * @var bool
     */
    private $should_redirect_user = false;
    /**
     * @var int
     */
    private $folder_id;

    public function __construct(\PFUser $user, \HTTPRequest $request, int $folder_id)
    {
        $this->user      = $user;
        $this->project   = $request->getProject();
        $this->request   = $request;
        $this->folder_id = $folder_id;
    }

    public function getUrlRedirection()
    {
        if ($this->folder_id === 0) {
            return "/plugins/document/" . urlencode($this->project->getUnixNameLowerCase()) . "/";
        }

        return "/plugins/document/" . urlencode($this->project->getUnixNameLowerCase()) . "/" . $this->folder_id;
    }

    /**
     * @return bool
     */
    public function shouldRedirectUserOnNewUI() : bool
    {
        return $this->should_redirect_user;
    }

    public function checkAndStoreIfUserHasToBeenRedirected(bool $should_use_document_url): void
    {
        if ($this->user->isAnonymous()) {
            return;
        }

        $is_request_for_legacy_docman = $this->request->exist("action");
        if ($is_request_for_legacy_docman) {
            $this->should_redirect_user = false;
            return;
        }

        $this->should_redirect_user = $should_use_document_url;
    }

    /**
     * @return \Project
     */
    public function getProject() : \Project
    {
        return $this->project;
    }
}
