<?php
namespace In2code\In2studyfinder\Tests\Unit\Domain\Model;

use In2code\In2studyfinder\Domain\Model\AcademicDegreeInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Sebastian Stein <sebastian.stein@in2code.de>, In2code GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for class \In2code\In2studyfinder\Domain\Model\AcademicDegree.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 * @author Sebastian Stein <sebastian.stein@in2code.de>
 */
class AcademicDegreeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var AcademicDegreeInterface
     */
    protected $subject = null;

    public function setUp()
    {
        $this->subject = new \In2code\In2studyfinder\Domain\Model\AcademicDegree();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getDegreeReturnsInitialValueForString()
    {
        $this->assertSame(
            '',
            $this->subject->getDegree()
        );
    }

    /**
     * @test
     */
    public function setDegreeForStringSetsDegree()
    {
        $this->subject->setDegree('Conceived at T3CON10');

        $this->assertAttributeEquals(
            'Conceived at T3CON10',
            'degree',
            $this->subject
        );
    }
}
