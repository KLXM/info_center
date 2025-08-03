<?php

namespace KLXM\InfoCenter;

use rex_addon;
use rex_be_controller;
use rex_view;

$package = rex_addon::get('info_center');
echo rex_view::title($package->i18n('info_center_title'));
rex_be_controller::includeCurrentPageSubPath();
