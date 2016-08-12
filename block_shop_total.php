<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_shop_total
 * @category   blocks
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->dirroot.'/local/shop/lib.php';
require_once $CFG->dirroot.'/local/shop/locallib.php';
require_once $CFG->dirroot.'/local/shop/classes/Shop.class.php';
require_once $CFG->dirroot.'/local/shop/classes/Catalog.class.php';

use local_shop\Shop;
use local_shop\Catalog;

class block_shop_total extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_shop_total');
    }

    function applicable_formats() {
        return array('all' => true, 'my' => true, 'course' => true);
    }

    function specialization() {
        return false;
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $USER, $CFG, $DB, $COURSE, $OUTPUT, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }

        list($theShop, $theCatalog, $theBlock) = shop_build_context();
        $view = optional_param('view', 'shop', PARAM_ALPHA);

        $this->content = new stdClass;

        $categories = $theCatalog->get_categories();
        $shopproducts = $theCatalog->get_all_products($categories);
        $units = 0;

        if (isset($SESSION->shoppingcart->order)) {
            foreach($SESSION->shoppingcart->order as $shortname => $q) {
                $units += $q;
            }
        }

        $renderer = shop_get_renderer('front');
        $renderer->load_context($theShop, $theCatalog, $theBlock);

        // Order total block.

        $this->content->text = '<a name="total"></a><div id="shop-ordertotals">';
        $this->content->text .= $renderer->order_totals();
        $this->content->text .= '</div>';

        // Order item counting block 

        $this->content->text .= $OUTPUT->heading(get_string('order', 'block_shop_total'));
        $this->content->text .= '<div id="order-detail">';
        $this->content->text .= $renderer->order_detail($shopproducts);
        $this->content->text .= '</div>';

        // Print navigation controller in right column

        $nextstyle = ($units) ? 'opacity:1.0' : 'opacity:0.5';
        $nextdisabled = ($units) ? '' : 'disabled="disabled"';
        $overtext = ($units) ? get_string('continue', 'block_shop_total') : get_string('emptyorder', 'block_shop_total');

        if ($view == 'shop') {
            $shopurl = new moodle_url('/local/shop/front/view.php');
            $this->content->text .= '<p align="center"><center>';
            $this->content->text .= '<form name="driverform" action="'.$shopurl.'">';
            $this->content->text .= '<input type="hidden" name="view" value="shop" />';
            $this->content->text .= '<input type="hidden" name="shopid" value="'.$theShop->id.'" />';
            if (!empty($theBlock)) {
                $this->content->text .= '<input type="hidden" name="blockid" value="'.$theBlock->instance->id.'" />';
            }
            $this->content->text .= '<input type="hidden" name="what" value="navigate" />';
            $this->content->text .= '<input type="submit" id="next-button" title="'.$overtext.'" name="go" value="'.get_string('next', 'block_shop_total').'" onclic="check_empty_order()" '.$nextdisabled.' style="'.$nextstyle.'" />';
            $this->content->text .= '<input type="button" name="clearall" value="'.get_string('reset', 'block_shop_total').'" onclick="this.form.what.value=\'clearall\';this.form.submit();" />';
            $this->content->text .= '</form>';
            $this->content->text .= '</center></p>';
        }

        $this->content->footer = '';

        unset($filteropt); // memory footprint

        return $this->content;
    }

    /*
     * Hide the title bar when none set..
     */
    function hide_header() {
        return empty($this->config->title);
    }
}
