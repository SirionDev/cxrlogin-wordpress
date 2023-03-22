=== Identitat Digital Republicana ===
Contributors: siriondev, gerardforcada
Donate link: https://consellrepublica.cat/
Tags: identitat-digital-republicana, cxr, consellrepublica, consell, republica, identitat, digital, republicana
Requires at least: 5.6
Tested up to: 6.2
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integració amb el procés de validació de la Identitat Digital Republicana del Consell de la República Catalana

== Description ==

Plugin de WordPress per a afegir la **Identitat Digital Republicana** als usuaris del teu lloc web i permetre l'inici de sessió mitjançant aquesta.

== Frequently Asked Questions ==

= El Consell de la República valida les identitats introduïdes? =

Si. Totes les **Identitats Digitals Republicanes** introduïdes són validades pel **Consell de la República**.

= Poden dos usuaris tenir la mateixa ID Republicana? =

No, les **ID Republicanes** hauran de ser úniques dins el lloc web.

== Screenshots ==

1. Inici de sessió amb l'ID Republicana
2. Configuració de l'ID Republicana

== Changelog ==

= 1.2.0 =
* Fixed issues with UM plugin
* Added menu entry in admin panel to manage IDR configs
* Extracted validation logic and added filter hooks so it can be reused programmatically

= 1.0.2 =
* Fixed regex validation for IDR (ID Republicana)

= 1.0.1 =
* Use Wordpress HTTP API instead of file_get_contents
* Sanitize, validate and escape data

== Upgrade Notice ==

= 1.2.0 =
* Fixed issues with UM plugin
* Added menu entry in admin panel to manage IDR configs
* Extracted validation logic and added filter hooks so it can be reused programmatically
