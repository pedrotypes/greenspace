Greenspace
==========

About
-----

An incomplete clone of Neptune's Pride for private use.


Setup
-----

Run the following commands:

```
git clone git@github.com:pgscandeias/greenspace.git
cd greenspace
cp frontend/app/config/parameters.yml.dist frontend/app/config/parameters.yml
vagrant up
vagrant ssh -c "cd /server/greenspace/frontend; composer install; bash scripts/init.sh"
echo '33.33.0.68 greenspace.dev' | sudo tee -a /etc/hosts
```

Then point your browser to [http://greenspace.dev](http://greenspace.dev)
