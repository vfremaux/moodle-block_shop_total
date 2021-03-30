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

/**
 * @package    block_shop_total
 * @category   blocks
 * @copyright  2016 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/shop/lib.php');
require_once($CFG->dirroot.'/local/shop/locallib.php');
require_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');
require_once($CFG->dirroot.'/local/shop/classes/Catalog.class.php');

use local_shop\Shop;
use local_shop\Catalog;

class block_shop_total extends block_base {

    public function init() {
        $this->title = get_string('order', 'block_shop_total');
    }

    public function applicable_formats() {
        return array('all' => true, 'my' => true, 'course' => true);
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function get_content() {
        global $OUTPUT, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }

        list($theshop, $thecatalog, $theblock) = shop_build_context();
        $view = optional_param('view', 'shop', PARAM_ALPHA);

        $this->content = new stdClass;

        $categories = $thecatalog->get_categories();
        $shopproducts = $thecatalog->get_all_products($categories);
        $units = 0;

        if (isset($SESSION->shoppingcart->order)) {
            foreach ($SESSION->shoppingcart->order as $q) {
                $units += $q;
            }
        }

        $renderer = shop_get_renderer('front');
        $renderer->load_context($theshop, $thecatalog, $theblock);

        // Order total block.
        $template = new StdClass;
        $template->ordertotals = $renderer->order_totals();

        // Order item counting block.
        $template->orderdetail = $renderer->order_detail($shopproducts);

        // Print navigation controller in right column.

        $template->nextstyle = ($units) ? 'opacity:1.0' : 'opacity:0.5';
        $template->nextdisabled = ($units) ? '' : 'disabled="disabled"';
        $template->overtext = ($units) ? get_string('continue', 'block_shop_total') : get_string('emptyorder', 'block_shop_total');
        $template->shopid = $theshop->id;
        if (is_object($theblock)) {
            $template->blockid = $theblock->instance->id;
        }

        $template->isshopview = false;
        if ($view == 'shop') {
            $template->isshopview = true;
            $template->shopurl = new moodle_url('/local/shop/front/view.php');
        }

        $this->content->text = $OUTPUT->render_from_template('block_shop_total/cart_total', $template);

        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Hide the title bar when none set.
     */
    public function hide_header() {
        return empty($this->config->title);
    }
}
