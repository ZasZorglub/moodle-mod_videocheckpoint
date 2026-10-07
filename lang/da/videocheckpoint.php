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
 * Video Checkpoint activity module.
 *
 * @package     mod_videocheckpoint
 * @copyright   2026 Simple Solutions
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcheckpoint'] = 'Tilføj endnu et quizstop';
$string['aidrafts'] = 'AI-genererede kladder';
$string['aidraftsgenerated'] = '{$a} spørgsmålsudkast blev genereret. Gennemgå dem før publicering.';
$string['aigeneration'] = 'AI-assisteret generering af spørgsmål';
$string['aigeneration_help'] = 'Transskriptionen sendes til den AI-udbyder, som Moodle-administratoren har konfigureret. Genererede spørgsmål forbliver kladder, indtil en lærer publicerer dem.';
$string['aigenerationfailed'] = 'AI-udbyderen kunne ikke generere gyldige spørgsmål: {$a}';
$string['answer'] = 'Indsend svar';
$string['checkpoint'] = 'Quizstop {$a}';
$string['checkpoints'] = 'Quizstop';
$string['completiondetail:questions'] = 'Besvar alle obligatoriske quizspørgsmål korrekt';
$string['completiondetail:watch'] = 'Se mindst {$a}% af videoen';
$string['completionpercent'] = 'Påkrævet set procentdel';
$string['completionquestions'] = 'Kræv korrekte svar på alle obligatoriske spørgsmål';
$string['completionwatch'] = 'Kræv en set procentdel';
$string['continuevideo'] = 'Fortsæt video';
$string['correct'] = 'Korrekt.';
$string['correctanswer'] = 'Korrekt svar';
$string['cueminute'] = 'Minutter';
$string['cuesec'] = 'Sekunder';
$string['delete'] = 'Slet';
$string['editactivity'] = 'Redigér alle aktivitetsindstillinger';
$string['feedback'] = 'Feedback efter svar';
$string['generatequestions'] = 'Generér spørgsmålsudkast';
$string['incorrect'] = 'Svaret er ikke korrekt. Prøv igen.';
$string['invalidcorrectanswer'] = 'Det valgte korrekte svar skal have en svartekst.';
$string['invalidvimeourl'] = 'Angiv en gyldig Vimeo-video-URL.';
$string['invalidwatchpercent'] = 'Den påkrævede seerprocent skal være mellem 1 og 100.';
$string['managequestions'] = 'Administrér AI-spørgsmålsudkast';
$string['missingvideo'] = 'Der er ikke konfigureret en afspillelig video.';
$string['modulename'] = 'Interaktiv video';
$string['modulename_help'] = 'Tilføj en uploadet eller Vimeo-hostet video med tracking og quizstop.';
$string['modulenameplural'] = 'Interaktive videoer';
$string['name'] = 'Navn';
$string['nodrafts'] = 'Der er ingen upublicerede AI-spørgsmålsudkast.';
$string['numberofquestions'] = 'Antal spørgsmål';
$string['optiona'] = 'Svar A';
$string['optionb'] = 'Svar B';
$string['optionc'] = 'Svar C';
$string['optiond'] = 'Svar D';
$string['optiongap'] = 'Svarmuligheder skal udfyldes i rækkefølge uden tomme felter imellem.';
$string['pluginadministration'] = 'Administration af interaktiv video';
$string['pluginname'] = 'Interaktiv video';
$string['privacy:metadata:answers'] = 'Svar indsendt ved videoens quizstop.';
$string['privacy:metadata:answers:answer'] = 'Det valgte svar og om det var korrekt.';
$string['privacy:metadata:external:ai'] = 'Når en lærer anmoder om AI-generering, sendes transskription og instruktioner gennem Moodles konfigurerede AI-udbyder.';
$string['privacy:metadata:external:vimeo'] = 'Hvis Vimeo vælges, forbinder browseren direkte til Vimeo, som kan behandle tekniske data og brugsdata.';
$string['privacy:metadata:progress'] = 'Afspilningsfremdrift gemt til gennemførelsestracking.';
$string['privacy:metadata:progress:activity'] = 'Den interaktive videoaktivitet.';
$string['privacy:metadata:progress:userid'] = 'Brugeren, hvis fremdrift gemmes.';
$string['privacy:metadata:progress:watched'] = 'Observeret afspilningsfremdrift.';
$string['progress'] = 'Fremdrift';
$string['publish'] = 'Publicér';
$string['questionrequired'] = 'Besvar spørgsmålet korrekt for at fortsætte.';
$string['questiontext'] = 'Spørgsmål';
$string['requiredquestion'] = 'Påkrævet for gennemførelse';
$string['sourceupload'] = 'Upload til Moodle';
$string['sourcevimeo'] = 'Vimeo';
$string['transcript'] = 'Transskription';
$string['transcript_help'] = 'Valgfri transskription til senere AI-generering af spørgsmål.';
$string['videocheckpoint:addinstance'] = 'Tilføj en interaktiv video';
$string['videocheckpoint:managequestions'] = 'Administrér quizspørgsmål';
$string['videocheckpoint:view'] = 'Se interaktiv video';
$string['videocheckpoint:viewreports'] = 'Se deltagernes fremdrift';
$string['videofile'] = 'Videofil';
$string['videofile_help'] = 'Upload én browserkompatibel MP4-video.';
$string['videosettings'] = 'Videokilde';
$string['videosource'] = 'Videokilde';
$string['videourl'] = 'Vimeo-URL';
$string['videourl_help'] = 'Brug en Vimeo-video-URL. Private videoer skal tillade indlejring på dette Moodle-domæne.';
$string['watchedpercent'] = '{$a}% set';
