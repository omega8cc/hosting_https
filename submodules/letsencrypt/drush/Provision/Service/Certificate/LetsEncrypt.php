<?php

/**
 *   A LetsEncrypt implementation of the Certificate service type.
 */
class Provision_Service_Certificate_LetsEncrypt extends Provision_Service_Certificate {
  public $service = 'LetsEncrypt';

  /**
   * Initialize this class, including option handling.
   */
  function init_server() {
    parent::init_server();

    /**
     * Register configuration classes for the create_config / delete_config methods.
     */
    $this->configs['server'][] = 'Provision_Config_LetsEncrypt';

    /**
     * Configurable values.
     */
    $this->server->setProperty('letsencrypt_ca', 'staging');

    /**
     * Non configurable values.
     */
    $this->server->letsencrypt_script_path = $this->server->aegir_root . '/config/letsencrypt';
    $this->server->letsencrypt_config_path = $this->server->aegir_root . '/config/letsencrypt.d';
    $this->server->letsencrypt_challenge_path = $this->server->aegir_root . '/config/letsencrypt.d/well-known/acme-challenge';
  }


  /**
   * Pass additional values to the config file templates.
   *
   * Even though the $server variable will be available in your template files,
   * you may wish to pass additional calculated values to your template files.
   *
   * Consider this something like the hook_preprocess stuff in drupal.
   */
  function config_data($config = null, $class = null) {
    // This format of calling the parent is very important!
    $data = parent::config_data($config, $class);

    /**
     * This value will become available as $letsencrypt_current_time
     * in all the config files generated by this service.
     *
     * You could also choose to only conditionally pass values based on
     * the parameters.
     */
    $data['letsencrypt_current_time'] = date(DATE_COOKIE, time());

    return $data;
  }

  /**
   * Return the path where we'll generate our certificates.
   */
  function get_source_path($https_key) {
    return "{$this->server->letsencrypt_config_path}/{$https_key}";
  }

  /**
   * Retrieve an array containing the actual files for this https_key.
   */
  function get_certificates($https_key) {
    $certs = parent::get_certificates($https_key);
    // This method is not strictly required, since it's just calling the parent
    // implementation. However, for illustrative purposes, this is where we'd
    // alter certificate paths, if we wanted to.
    return $certs;
  }

  /**
   * Retrieve an array containing source and target paths for this https_key.
   */
  function get_certificate_paths($https_key) {
    $source_path = $this->get_source_path($https_key);
    $target_path = "{$this->server->http_ssld_path}/{$https_key}";

    $certs = array();
    $certs['https_cert_key_source'] = "{$source_path}/privkey.pem";
    $certs['https_cert_key'] = "{$target_path}/openssl.key";
    $certs['https_cert_source'] = "{$source_path}/cert.pem";
    $certs['https_cert'] = "{$target_path}/openssl.crt";
    $certs['https_chain_cert_source'] = "{$source_path}/fullchain.pem";
    $certs['https_chain_cert'] = "{$target_path}/openssl_chain.crt";

    return $certs;
  }

  /**
   * Generate a self-signed certificate for the provided key.
   *
   * Because we only generate certificates for sites we make some assumptions
   * based on the uri, but this cert may be replaced by the admin if they
   * already have an existing certificate.
   */
  function generate_certificates($https_key) {
    $path = $this->get_source_path($https_key);
    provision_file()->create_dir($path,
    dt("HTTPS certificate directory for %https_key", array(
      '%https_key' => $https_key
    )), 0700);

    // TODO: If we ever need more granular control, we can generate the config
    // file instead.
    switch (d()->server->letsencrypt_ca) {
      case 'production':
        $config_file = 'config';
        break;
      case 'staging':
      default:
        $config_file = 'config.staging';
    }

    $script_path = d()->server->letsencrypt_script_path;
    $config_path = d()->server->letsencrypt_config_path;
    $uri = d()->uri;
    drush_log(dt("Generating Let's Encrypt certificates."));
    $result = drush_shell_exec("{$script_path}/script -c -f {$script_path}/{$config_file} --out {$config_path} -d {$uri} -x");
    foreach (drush_shell_exec_output() as $line) {
      drush_log($line);
    }
    if ($result) {
      drush_log(dt("Successfully generated Let's Encrypt certificates."), 'success');
    }
    else {
      drush_log(dt("Failed to generate Let's Encrypt certificates."), 'warning');
    }
  }

  /**
   * Implementation of service verify.
   *
   * Called from drush_certificate_provision_verify().
   */
  function verify() {
    parent::verify();
    if ($this->context->type == 'server') {
      // Create the configuration file directory.
      provision_file()->create_dir($this->server->letsencrypt_config_path, dt("Let's Encrypt configuration directory"), 0711);
      // Create the ACME challenge directory.
      provision_file()->create_dir($this->server->letsencrypt_challenge_path, dt("Let's Encrypt ACME challenge directory"), 0711);
      // Copy the script directory into place.
      $source = dirname(dirname(dirname(dirname(__FILE__)))) . '/bin/';
      if (drush_copy_dir($source, $this->server->letsencrypt_script_path, FILE_EXISTS_OVERWRITE)) {
        drush_log("Copied Let's Encrypt script directory into place.", 'success');
      }
      // Sync the directory to the remote server if needed.
    #  $this->sync($this->server->letsencrypt_config_path);
    }
  }
}