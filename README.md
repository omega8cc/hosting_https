# Aegir HTTPS

This module enables HTTPS support for sites within the [Aegir Hosting System](http://www.aegirproject.org/) using certificate management services such as [Let's Encrypt](https://letsencrypt.org/), whose support is included.

It provides a cleaner, more sustainable and more extensible implementation that what's currently offered in Aegir SSL within Aegir core, and doesn't require workarounds such as [hosting_le](https://github.com/omega8cc/hosting_le).

## Installation

1. Cleanup old SSL usage.
    * Check that the hostmaster site is not set to Encryption: Required. (e.g. on /hosting/c/hostmaster) to avoid locking yourself out.
    * Edit the server nodes(e.g. /hosting/c/server_master) to not use an SSL service.
    * Disable any of the SSL modules (including hosting_le) you may have already enabled.
2. Switch to the directory where you wish to install the module.
    * cd /var/aegir/hostmaster-7.x-3.8/sites/aegir.example.com/modules/contrib
3. Download this module.  This command will include the required PHP library.
    * git clone --recursive https://gitlab.com/aegir/hosting_https.git
4. Surf to Administration » Hosting » Experimental » Aegir HTTPS.
5. Enable at least one certificate service (e.g. Let's Encrypt or Self-signed).
6. Enable at least one Web serrver service (e.g. Apache HTTPS or Nginx HTTPS).
7. Save the configuration.

## Server Set-Up

1. Surf to the Servers tab.
2. Click on the Web server where you'd like HTTPS enabled.
3. Click on the Edit tab.
4. Under Certificate, choose your desired certificate service (and set any of its additional configuration).
5. Under Web, choose the HTTPS option for your Web server (and set any of its additional configuration).
6. Hit the Save button.

## Site Set-Up

1. Ensure that there's a DNS entry for the site that you'd like HTTPS enabled (unless already handled by a wildcard entry pointing to your Aegir server).
2. Verify the site if this hasn't been done since the server was set up with the above steps.  This ensures that the site can respond to the certificate authority's challenge.
3. Edit the site.
4. In the HTTPS Settings section, choose either Enabled or Required.
5. Save the form.
6. Repeat these steps for any other sites for which you'd like to enable HTTPS.

## Certificate Renewals

For the Let's Encrypt certificate service, this should get done automatically via the Let's Encrypt queue. It will run a Verify task on each site every week as site verification is where certificates get renewed if needed. The seven-day default was chosen to match the CA's [rate limits](https://letsencrypt.org/docs/rate-limits/).

## Known Issues

See [the issue queue](https://gitlab.com/aegir/hosting_https/issues).