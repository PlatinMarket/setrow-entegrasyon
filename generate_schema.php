rm -f -v ~/Sites/setrow-entegrasyon/app/Config/Schema/schema.php
cake -app ~/Sites/setrow-entegrasyon/app schema generate
cake -app ~/Sites/setrow-entegrasyon/app Db dump
