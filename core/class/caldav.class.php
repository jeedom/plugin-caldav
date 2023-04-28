<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
//require_once dirname(__FILE__) . '/../../3rdparty/caldav-client.php';
if ( ! class_exists ("SimpleCalDAVClient") )
{
	require_once dirname(__FILE__) . '/../../3rdparty/simpleCalDAV/SimpleCalDAVClient.php';
}
//require_once dirname(__FILE__) . '/../../3rdparty/caldav-client-v2.php';


class caldav extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

	public static function pull() {
		foreach (self::byType('caldav') as $eqLogic) {
			$eqLogic->scan();
		}
	}

	public function preUpdate()
	{
		if ( $this->getIsEnable() )
		{
			$client = new SimpleCalDAVClient();
			log::add('caldav', 'debug', 'Check calendar access');
			try {
				$client->connect($this->getConfiguration('url'), $this->getConfiguration('username'), $this->getConfiguration('password'));
				log::add('caldav', 'debug', 'Calendar access OK');
				if ( $this->getConfiguration('calendrier') != "" )
				{
					log::add('caldav', 'debug', 'Check calendar exist');
					$arrayOfCalendars = $client->findCalendars();
					if ( isset($arrayOfCalendars[$this->getConfiguration('calendrier')]))
					{
						log::add('caldav', 'debug', 'Calendar find');
					}
					else
					{
						log::add('caldav', 'error', 'Unable to find '.$this->getConfiguration('calendrier').' calendar');
					}
				}
			} catch (Exception $e) {
				log::add('caldav', 'error', 'Unable to validate access : '.$e->__toString());
			}
		}
	}

    public function getCalendars() {
		if ( $this->getIsEnable() ) {
			try {
				$desc_event = array();
				$events = array();
				$time = time();
				$client = new SimpleCalDAVClient();
				$client->connect($this->getConfiguration('url'), $this->getConfiguration('username'), $this->getConfiguration('password'));
				log::add('caldav', 'debug', 'Find calendar');
				$arrayOfCalendars = $client->findCalendars();
				return array_keys ($arrayOfCalendars);
			} catch (Exception $e) {
				log::add('caldav', 'error', 'URL non valide ou accès internet invalide : ' .  $e->__toString());
#				throw $e;
			}
		}
	}
	
	public function scan() {
		if ( $this->getIsEnable() && $this->getConfiguration('calendrier') != "" ) {
			try {
				$desc_event = array();
				$events = array();
				$time = time();
				$client = new SimpleCalDAVClient();
				$client->connect($this->getConfiguration('url'), $this->getConfiguration('username'), $this->getConfiguration('password'));
				log::add('caldav', 'debug', 'Chose calendar '.$this->getConfiguration('calendrier'));

				$arrayOfCalendars = $client->findCalendars();
				$client->setCalendar($arrayOfCalendars[$this->getConfiguration('calendrier')]);
				try {
					log::add('caldav', 'debug', 'Recupere les évenements entre '.date("Ymd\THi00\Z", $time).' et '.date("Ymd\THi59\Z", $time));
					$events = $client->getEvents(gmdate("Ymd\THi00\Z", $time),gmdate("Ymd\THi59\Z", $time));
				} catch (Exception $e) {
					log::add('caldav', 'debug', 'Aucun event');
					$events = array();
				}
				log::add('caldav', 'debug', 'Trouve '.count($events).' events');
				foreach ( $events AS $event ) {
					$data = $event->getData();
					//log::add('caldav', 'debug', 'Event => '.print_r($data, true));
					foreach ( explode("\n", $data) AS $debug) {
						//log::add('caldav', 'debug', 'debug : '.$debug);
						if ( preg_match("!^(.*):(.*)$!", $debug, $regs) ) {
							if ( strrpos($regs[1], "SUMMARY", strlen($regs[1])===strlen("SUMMARY")? -strlen("SUMMARY"):strlen($regs[1]) ) !== false ) {
								log::add('caldav', 'debug', 'Trouve '.chop($regs[2]));
								array_push($desc_event, chop($regs[2]));
							}
						}
					}
					//break;
				}
				log::add('caldav', 'debug', 'Recherche correspondance cmd');
				foreach ($this->getCmd() as $cmd) {
					$value = $cmd->extract($desc_event);
					if ($value != $cmd->execCmd()) {
						$cmd->setCollectDate(date('Y-m-d H:i:s'));
						$cmd->event($value);
					}
				}
			} catch (Exception $e) {
				log::add('caldav', 'error', 'URL non valide ou accès internet invalide : ' .  $e->__toString());
#				throw $e;
			}
		}
    }

    /*     * *********************Methode d'instance************************* */
}

class caldavCmd extends cmd {
    public function preSave() {
        $this->setEventOnly(1);
    }

    public function extract($events = array()) {
		$result = array();
		if ( count($events) != 0 ) {
			foreach ( $events AS $event ) {
				if ( $this->getConfiguration('pattern') == '' ) {
					log::add('caldav', 'debug', 'Correspond sans pattern');
					array_push($result, $event);
				} elseif ( preg_match($this->getConfiguration('pattern'), $event, $regs) ) {
					if ( !isset($regs["1"]) || $regs["1"] == '' ) {
						log::add('caldav', 'debug', 'Correspond avec pattern trouve');
						array_push($result, $event);
					} else {
						log::add('caldav', 'debug', 'Correspond avec pattern et trouve : '.$regs["1"]);
						array_push($result, $regs["1"]);
					}
				}
			}
		}
		if (count($result) == 0) {
			log::add('caldav', 'debug', 'Acune valeur trouve');
			if ($this->getConfiguration('defaultValue') == '') {
				return __('Aucun', __FILE__);
			} else {
				return $this->getConfiguration('defaultValue');
			}
		}
        return join(';', $result);
    }

    public function execute($_options = array()) {
		$EqLogic = $this->getEqLogic();
		$EqLogic->scan();
		return $this->execCmd();
	}
    /*     * **********************Getteur Setteur*************************** */
}
?>
