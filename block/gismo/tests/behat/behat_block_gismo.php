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
 * Gismo steps definitions.
 *
 * @package    core_gismo
 * @category   test
 * @copyright  2015 Corbière Alain 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

// http://docs.behat.org/en/latest/guides/2.definitions.html
// http://docs.behat.org/en/v2.5/guides/4.context.html \moodle271\lib\behat\features\bootstrap\behat_init_context.php
// Chained steps are deprecated. See https://docs.moodle.org/dev/Acceptance_testing/Migrating_from_Behat_2.5_to_3.x_in_Moodle#Changes_required_in_context_file

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions to deal with the gismo component
 *
 * @package    core_gismo
 * @category   test
 * @copyright  2015 Corbière Alain <alain.corbiere@univ-lemans.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_gismo extends behat_base {

    /**
     * Synchronizes Gismo data.
     *
     * @Given /^I synchronize gismo data$/
     */
    public function i_synchronize_gismo_data() {
		$plugin = new stdClass();
		include(__DIR__ . "/../../version.php") ;
		if ($plugin->version > 2013101501) {
			// Version 3.3+
			ob_start();
			include(__DIR__ . "/../../lib/gismo/server_side/export_data.php") ;
			$endOkMessageExportData = "GISMO - export data (end)!<br />" ;
			if (substr_compare(ob_get_clean(), $endOkMessageExportData, -strlen($endOkMessageExportData))!== 0)
				throw new Exception('Export data problem in the GISMO block (export_data.php)');
		}
		else {
			// Version 3.2-
			$this->getSession()->visit($this->locate_path('/blocks/gismo/lib/gismo/server_side/export_data.php?password=')) ;
			$this->getSession()->back() ;
		}
    }

    /**
     *  Select a reporting.
     *
     * @Given /^I go to the "(?P<overview>(?:[^"]|\\")*)" report$/
     * @param string $overview The menu item
     */
    public function i_go_to_the_gismo_report($overview) {
		$this->execute('behat_general::click_link', get_string('gismo_report_launch', 'block_gismo'));
		$this->i_select_a_reporting_on($overview) ;
		$this->i_move_backward_one_page() ;
    }

    /**
     *  Select overview by gismo without back navigation.
     *
     * @Given /^I select a reporting on "(?P<parentnodes_string>(?:[^"]|\\")*)"$/
     * @param string $parentnodes_string The menu items
     */
    public function i_select_a_reporting_on($parentnodes) {
        if ($this->running_javascript()) {
            $parentnodes = array_map('trim', explode('>', $parentnodes));
            $javascript = $this->getSession()->getPage()->find("xpath", "//a[contains(text(),'" . $parentnodes[0] . "')]/../ul/li/a/div/nobr[contains(text(),'" . $parentnodes[1] . "')]/../..")->getAttribute("href");
            $this->getSession()->executeScript($javascript);
            $this->getSession()->wait(self::TIMEOUT * 1000, false);
        }
    }

    /**
     *  Compare number on overview by gismo.
     *
     * @Then /^I should see "(?P<element_string>(?:[^"]|\\")*)" on "(?P<parentnodes_string>(?:[^"]|\\")*)" report$/
     * @throws ExpectationException
     * @param string $element_string The show element
     * @param string $parentnodes_string The menu items
     */
    public function i_should_see_accesses_on_overview($element, $parentnodes) {
		$this->execute('behat_general::click_link', get_string('gismo_report_launch', 'block_gismo'));
		$this->i_select_a_reporting_on($parentnodes) ;
		$this->i_see($element) ;
    }

    /**
     *  Compare number/text on accesses overview by gismo.
     *
     * @Then /^I see "(?P<element_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $element_string The show element
     */
    public function i_see($element) {
        if ($this->running_javascript()) {
            // line 192 de gismo.js.php
            // accesses overview in resources: this.current_analysis.prepared_data["lines"] 
            // g.current_analysis.prepared_data[\"lines\"][0][0][2] = 1 and not 2 ([\"lines\"][0][1]) on "Activities > Forums over time" report
			// Selenium2Driver (Mink v 1.7.1)
			// public function evaluateScript($script)
            // {
            //   if (0 !== strpos(trim($script), 'return ')) {
            //     $script = 'return ' . $script;
            // }
			$javascript = "return function() { if (g.current_analysis.prepared_data[\"lines\"] !== undefined && typeof g.current_analysis.prepared_data[\"lines\"] !== \"object\" ) " .
                    " return (g.current_analysis.prepared_data[\"lines\"]); }()";
            $elementReturn = $this->getSession()->evaluateScript($javascript);
            if (is_null($elementReturn)) {
                $javascript = "return function() { if (g.current_analysis.prepared_data[\"lines\"][0] !== undefined && typeof g.current_analysis.prepared_data[\"lines\"][0] !== \"object\" ) " .
                        " return (g.current_analysis.prepared_data[\"lines\"][0]); }()";
                $elementReturn = $this->getSession()->evaluateScript($javascript);
                if (is_null($elementReturn)) {
                    // accesses by students & accesses overview : this.current_analysis.prepared_data["lines"][0][1]
                    $javascript = "return function() { if (g.current_analysis.prepared_data[\"lines\"][0][1] !== undefined && typeof g.current_analysis.prepared_data[\"lines\"][0][1] !== \"object\" )  " .
                            " return (g.current_analysis.prepared_data[\"lines\"][0][1]); }()";
                    $elementReturn = $this->getSession()->evaluateScript($javascript);
                    if (is_null($elementReturn)) {
                        // students overview & accesses overview : this.current_analysis.prepared_data["lines"][0][0][2]
                        $javascript = "return function() { if (g.current_analysis.prepared_data[\"lines\"][0][0][2] !== undefined && typeof g.current_analysis.prepared_data[\"lines\"][0][0][2] !== \"object\" )  " .
                                " return (g.current_analysis.prepared_data[\"lines\"][0][0][2]); }()";
                        $elementReturn = $this->getSession()->evaluateScript($javascript);
                    }
                }
            }
            if (is_numeric($element)) {
                if ($element != $elementReturn)
                    throw new ExpectationException('The element is "' . $elementReturn . '" and not "' . $element . '"', $this->getSession());
            }
            else {
                if (strpos(strtolower($elementReturn), strtolower($element)) === false)
                    throw new ExpectationException('The element is "' . $elementReturn . '" and not "' . $element . '"', $this->getSession());
            }
            $this->getSession()->back();
        }
    }

    /**
     * @Given /^I move backward one page$/
     */
    public function i_move_backward_one_page() {
        $this->getSession()->back();
    }
	
    /**
     * Opens Moodle site homepage. (New step in version 2.9)
     *
     * @Given /^I am on site homepage \(New step defintion in version 2.9\)$/
	 * https://github.com/moodle/moodle/blob/v2.9.0/lib/tests/behat/behat_general.php#L87
     */
    public function i_am_on_site_homepage_new_step_in_version() {
        $this->getSession()->visit($this->locate_path('/?redirect=0'));
    }

    /**
     * Clicks link specific link to the cat.
     *
     * @When /^I follow chat link "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $link
     */
    public function click_chat_link($link) {
        $linknode = $this->find_link($link);
		$this->getSession()->visit($linknode->getAttribute("href")) ;
    }
	
}
