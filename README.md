# Download Contestio plugin for Magento with composer
```bash
composer require contestio/magento
```

# Activate the plugin
```bash
bin/magento module:enable Contestio_Connect
bin/magento setup:upgrade
```

# (Prerequisite) Disable Magento 2 cache :
- Go to the Magento 2 admin panel
- Go to System > Cache Management
- Disable following caches:
  - Blocks HTML output
  - GraphQL Query Resolver Results
  - Page Cache


# Add your API key on the Magento admin panel
```bash
Admin Panel > Stores > Configuration > Contestio > Connect
```
