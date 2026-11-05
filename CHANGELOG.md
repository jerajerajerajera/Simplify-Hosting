# Changelog

## Unreleased

## 2.0.0-beta.5

- The browser no longer fills a saved user name into the link fields, which made Save fail.
- With Simplify 2.0 the settings are in Simplify settings › Add-ons, and Hosting no longer has its own item in the admin menu. Without Simplify, nothing changes.
- Uninstalling keeps the hoster's settings: installing again brings them back.
- Checks: PHPStan (level 5) runs in CI, with no findings left. CI also tests the update from 2.0.0-beta.1 with every setting filled in, uninstalling and installing again, and going back to the previous release.

## 2.0.0-beta.4

- Version aligned with Simplify 2.0.0-beta.4.

## 2.0.0-beta.2

- First version on the Hub, as a beta. The same as 2.0.0-beta.1.

## 2.0.0-beta.1

- First version on the Hub.
- With Simplify 1.5.6 and newer, "Open in host mode" makes admins of a panel with more than one node start on Simplify's Admin page (Pelican's Health page), once per session. With older Simplify versions it opens Simplify's host mode as before.

## 1.0.4

- Pick your billing system (WHMCS, Paymenter or Blesta) and enter its address: Hosting fills in the billing, client area and order links. You can still change them.
- A suspended server offers its customer Pay invoice (the billing link) instead of Upgrade this server. Simplify 1.5.5 and newer show it on the server's card; with older versions nothing changes there.

## 1.0.3

- With a Simplify version newer than 1.5.1, Client area is back as an icon in Simplify's account menu, as in PelicanSimplify 0.7.0. With older versions nothing changes.

## 1.0.2

- The settings page no longer scrolls as a whole page with Simplify 1.5: the form scrolls under the page title.

## 1.0.1

- Simplify 1.3's setup now also asks for your billing, client area and order links and the host mode default, so a new panel is set up in one go. On older Simplify versions, or without Simplify, nothing changes.

## 1.0.0

First version. The hosting settings of Simplify 1.0 moved here.

- **Billing link** per server: "Upgrade this server", with `{uuid}` and `{external_id}` filled in.
- **Client area** in everyone's account menu, and **Order a server** for customers.
- **Host mode** as the default for admins of a panel with more than one node (with Simplify).
- With Simplify, customers get a greeting with these links above their servers.
- Takes over the hosting settings of Simplify 1.0 once.
- In the Simplify group of Pelican's admin menu, with the other Simplify add-ons.
