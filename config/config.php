<?php

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  contao4you.de - Oliver Lohoff - 2012
 * @author     Oliver Lohoff <info@contao4you.de>
 * @package    CeGalleryXT
 * @license    LGPL
 * @filesource
 */


array_insert($GLOBALS['TL_CTE']['media'],2,array(
    'cegalleryxt'   => 'ContentGalleryXT'
));

// damit bei Dateiauswahl Bilder angezeigt werden
$GLOBALS['TL_GALLERIES'][] = 'cegalleryxt';
