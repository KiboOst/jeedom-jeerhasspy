<?php
$configformulaire = [
	"jeedom_lumiere" => [
		"titre" => "Gestion de la lumière",
		"module" => [
			"select_objet" => [
				"type" => "select",
				"name" => "objet",
				"balise" => "Objet : #objet#",
				"size" => 10,
				"option" => "_objet"
			],
			"saisi_nom_equipement" => [
				"type" => "text",
				"name" => "nom_equipement",
				"balise" => "Nom des équipements (séparer par des ';') : #equipement#",
				"option" => "lampe;plafonnier;led;lumière;spot"
			],
			"saisi_mot_custom" => [
				"type" => "text",
				"name" => "custom_word",
				"balise" => "mot custom (séparer par des ';') :",
				"option" => "raspberry R a s p b e R i;kodi K o d I"
			],
			"build_phrase" => [
				"type" => "build_phrase",
				"div" => [
					["type" => "texte","class" => "selectBox", "text" => "phase : "],
					["type" => "texte","class" => "textintent", "text" => "&lt;commande&gt;"],
					["type" => "checkbox","class" => "checkboxes", "text" => [
						["name" => "jeedom_lumiere11", "text" => "allume | éteint"],
						["name" => "jeedom_lumiere21", "text" => "ouvre | ferme"],
						["name" => "jeedom_lumiere31", "text" => "met à 1 | met à 0"]
					]],
					["type" => "texte","class" => "textintent", "text" => "[la] [le] [les] [l']"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;equipement&gt;"],
					["type" => "texte","class" => "textintent", "text" => "[de] [à] [l'] [la] [du] [de la]"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;objet&gt;"]
				]
			],
			"saisi_commande" => [
				"type" => "multitext",
				"name" => ["on_commande","off_commande"],
				"balise" => ["commande jeedom pour activer:","commande jeedom pour désactiver:"],
				"option" => ["on;allumer","off;eteindre"]
			],
			"saisi_retour" => [
				"type" => "text",
				"name" => "retour",
				"balise" => "message de retour:",
				"option" => "c'est fait"
			],
			"select_category" => [
				"type" => "select",
				"name" => "category",
				"balise" => "Catégorie : type d'équipement jeedom",
				"size" => 10,
				"option" => ["noCategory" => "0", "heating" => "0", "security" => "0", "energy" => "0", "light" => "1", "opening" => "0", "automatism" => "0", "multimedia" => "0", "default" => "0"]
			]
		]
	],
	"jeedom_chauffage" => [
		"titre" => "Gestion du chauffage",
		"module" => [
			"select_objet" => [
				"type" => "select",
				"name" => "objet",
				"balise" => "Objet : #objet#",
				"size" => 10,
				"option" => "_objet"
			],
			"saisi_nom_equipement" => [
				"type" => "text",
				"name" => "nom_equipement",
				"balise" => "Nom des équipements (séparer par des ';') : #equipement#",
				"option" => "radiateur;chauffage"
			],
			"saisi_mot_custom" => [
				"type" => "text",
				"name" => "custom_word",
				"balise" => "mot custom (séparer par des ';') :",
				"option" => "raspberry R a s p b e R i;kodi K o d I"
			],
			"build_phrase" => [
				"type" => "build_phrase",
				"div" => [
					["type" => "texte","class" => "selectBox", "text" => "phase : "],
					["type" => "texte","class" => "textintent", "text" => "&lt;commande&gt;"],
					["type" => "checkbox","class" => "checkboxes", "text" => [
						["name" => "jeedom_chauffage11", "text" => "allume | éteint"],
						["name" => "jeedom_chauffage21", "text" => "ouvre | ferme"],
						["name" => "jeedom_chauffage31", "text" => "met à 1 | met à 0"]
					]],
					["type" => "texte","class" => "textintent", "text" => "[la] [le] [les] [l']"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;equipement&gt;"],
					["type" => "texte","class" => "textintent", "text" => "[de] [à] [l'] [la] [du] [de la]"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;objet&gt;"]
				]
			],
			"saisi_commande" => [
				"type" => "multitext",
				"name" => ["on_commande","off_commande"],
				"balise" => ["commande jeedom pour activer:","commande jeedom pour désactiver:"],
				"option" => ["on;allumer","off;eteindre"]
			],
			"saisi_retour" => [
				"type" => "text",
				"name" => "retour",
				"balise" => "message de retour:",
				"option" => "c'est fait"
			],
			"select_category" => [
				"type" => "select",
				"name" => "category",
				"balise" => "Catégorie : type d'équipement jeedom",
				"size" => 10,
				"option" => ["noCategory" => "0", "heating" => "1", "security" => "0", "energy" => "0", "light" => "0", "opening" => "0", "automatism" => "0", "multimedia" => "0", "default" => "0"]
			]
		]
	],
	"jeedom_volet" => [
		"titre" => "Gestion des volets",
		"module" => [
			"select_objet" => [
				"type" => "select",
				"name" => "objet",
				"balise" => "Objet : #objet#",
				"size" => 10,
				"option" => "_objet"
			],
			"saisi_nom_equipement" => [
				"type" => "text",
				"name" => "nom_equipement",
				"balise" => "Nom des équipements (séparer par des ';') : #equipement#",
				"option" => "volet;rideau;fenètre"
			],
			"saisi_mot_custom" => [
				"type" => "text",
				"name" => "custom_word",
				"balise" => "mot custom (séparer par des ';') :",
				"option" => "raspberry R a s p b e R i;kodi K o d I"
			],
			"build_phrase" => [
				"type" => "build_phrase",
				"div" => [
					["type" => "texte","class" => "selectBox", "text" => "phase : "],
					["type" => "texte","class" => "textintent", "text" => "&lt;commande&gt;"],
					["type" => "checkbox","class" => "checkboxes", "text" => [
						["name" => "jeedom_volet11", "text" => "allume | éteint"],
						["name" => "jeedom_volet21", "text" => "ouvre | ferme"],
						["name" => "jeedom_volet31", "text" => "met à 1 | met à 0"],
						["name" => "jeedom_volet41", "text" => "monte | descend"]
					]],
					["type" => "texte","class" => "textintent", "text" => "[la] [le] [les] [l']"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;equipement&gt;"],
					["type" => "texte","class" => "textintent", "text" => "[de] [à] [l'] [la] [du] [de la]"],
					["type" => "texte","class" => "textintent selectBox", "text" => "&lt;objet&gt;"]
				]
			],
			"saisi_commande" => [
				"type" => "multitext",
				"name" => ["on_commande","off_commande"],
				"balise" => ["commande jeedom pour activer:","commande jeedom pour désactiver:"],
				"option" => ["on;allumer","off;eteindre"]
			],
			"saisi_retour" => [
				"type" => "text",
				"name" => "retour",
				"balise" => "message de retour:",
				"option" => "c'est fait"
			],
			"select_category" => [
				"type" => "select",
				"name" => "category",
				"balise" => "Catégorie : type d'équipement jeedom",
				"size" => 10,
				"option" => ["noCategory" => "0", "heating" => "0", "security" => "0", "energy" => "0", "light" => "0", "opening" => "1", "automatism" => "0", "multimedia" => "0", "default" => "0"]
			]
		]
	]
];
foreach ($configformulaire as $key => $value) {
	config::save('config-'.$key, json_encode($value), 'jeerhasspy');
}
?>