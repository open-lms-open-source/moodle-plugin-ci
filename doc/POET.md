# POET Group

To learn about the POET Group, please see visit http://poetgroup.org

In order to run POET Group's automated code review, simply add the following to your `.travis.yml` file:

```yaml
script:
  - moodle-plugin-ci codechecker --standard poet
```

Please note that this does **not** replace the `moodle-plugin-ci codechecker` call which validates a plugin against
Core Moodle's coding standard.  Also know that the POET Group's standard attempts to find *potential* problems within
a plugin, so it might not be possible to run it without getting some warnings.
