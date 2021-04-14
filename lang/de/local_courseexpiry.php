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
 * @package    local_courseexpiry
 * @copyright  2021 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Kurs-Ablauflöschung';

$string['checkstops'] = 'Prüftage';
$string['checkstops:description'] = 'Gibt jene Tage an, an denen die Kurse auf ihr Ablaufdatum geprüft werden. Geben Sie mehrere Tage im Format mmdd zeilenweise ein.';

$string['delete'] = 'Löschen';

$string['excludedremoval'] = 'Von der Löschung ausgenommen';
$string['expired_courses'] = 'Zu löschende Kurse';
$string['expired_courses:explanation'] = 'Die folgende Liste zeigt alle abgelaufenen Kurse, für die eine automatische Löschung geplant wurde. Bitte markieren Sie jene Kurse, die Sie behalten möchten.';

$string['ignorecategories'] = 'Ignoriere Kursbereiche';
$string['ignorecategories:description'] = 'Geben Sie hier Kursbereiche ein, die von diesem Plugin ignoriert werden sollen. Trennen Sie mehrere IDs mit einem Beistrich.';
$string['ignorecourses'] = 'Ignoriere Kurse';
$string['ignorecourses:description'] = 'Geben Sie hier Kurse ein, die von diesem Plugin ignoriert werden sollen. Trennen Sie mehrere IDs mit einem Beistrich.';

$string['keep'] = 'Behalten';

$string['listempty'] = 'Es wurden keine Kurse für die Löschung eingeplant.';

$string['notify:subject'] = 'Kurse sind abgelaufen';
$string['notify:html'] = '
    <h3>Sehr geehrte/r {$a->fullname}</h3>
    <p>
        Wir haben kürzlich festgestellt, dass manche Ihrer Kurse abgelaufen sind, in denen
        Sie als Lernbegleiter/in eingeschrieben sind. Alle abgelaufenen Kurse werden
        automatisch nach {$a->timetodeletionweeks} Wochen gelöscht.
    </p>
    <p>
        Falls Sie abgelaufene Kurse behalten möchten, folgen Sie bitte dem Link, und markieren
        Sie jene Kurse, die Sie behalten möchten.
    </p>
    <p>
        <a href="{$a->wwwroot}/local/courseexpiry/expiredcourses.php">
            {$a->wwwroot}/local/courseexpiry/expiredcourses.php
        </a>
    </p>';

$string['status'] = 'Status';
$string['scheduledremoval'] = 'Zur Löschung eingeplant';

$string['task'] = 'Kurs-Ablauf Task';
$string['timedelete'] = 'Tag der Löschung';
$string['timetodeletion'] = 'Zeit bis zur Löschung';
$string['timetodeletion:description'] = 'Gibt die Anzahl an Wochen an, die vor der endgültigen Löschung abgewartet werden.';
$string['timetodeletionweeks_immediate'] = 'Sofort';
$string['timetodeletionweeks_single'] = '1 Woche';
$string['timetodeletionweeks'] = '{$a->weeks} Wochen';
