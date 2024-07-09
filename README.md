# Download Contestio plugin for Magento with composer
```bash
composer require contestio/magento
```

# Activate the plugin
```bash
bin/magento module:enable Contestio_Connect
bin/magento setup:upgrade
```

# Add your API key on the Magento admin panel
```bash
Admin Panel > Stores > Configuration > Contestio > Connect
```
