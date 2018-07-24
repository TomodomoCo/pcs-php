# PCS PHP

A simple PHP client for Publisher's Creative System's [SOAP API](http://pcspublink.com/technology-and-tools/api-and-xml-web-services/).

## Usage

The client must be installed with Composer, as a manual VCS repository (it is not currently available in Packagist).

To set up the client:

```php
// Create a new instance of the API client with your pubcode and API password
$pcs = new PCS('PUBCODE', 'API password');
```

To check if a user is active:

```php
$status = $pcs->isUserActive('user@email.com', 'secret password');
// returns true or false
```

More to come…

## About Tomodomo

Tomodomo is a creative agency for magazine publishers. We use custom design and technology to speed up your editorial workflow, engage your readers, and build sustainable subscription revenue for your business.

Learn more at [tomodomo.co](https://tomodomo.co) or email us: [hello@tomodomo.co](mailto:hello@tomodomo.co)

## License & Conduct

This project is licensed under the terms of the MIT License, included in `LICENSE.md`.

All open source Tomodomo projects follow a strict code of conduct, included in `CODEOFCONDUCT.md`. We ask that all contributors adhere to the standards and guidelines in that document.

Thank you!
