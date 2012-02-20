<?php /* #?ini charset="utf-8"?

[Cache]
CacheItems[]=ezmemcachedcluster

[Cache_ezmemcachedcluster]
name=eZMemcachedCluster
id=ezmemcachedcluster
# No tags since every cache is being handled through eZClusterFileHandler
tags[]
class=eZMemcachedClusterCacheClear
purgeClass=eZMemcachedClusterCacheClear

*/ ?>
