<template> </template>
<script>
export default {
  data() {
    return {};
  },
  beforeMount() {
    if (!this.$session.has("mpr")) {
      this.$router.push("./");
    }
  }
};
</script>