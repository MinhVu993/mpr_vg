<template>
  <div class="print-wrapper">
    <PrintRequest 
      v-for="id in ids" 
      :key="id" 
      :id="id" 
      class="page-break-after" 
    />
  </div>
</template>

<script>
import PrintRequest from '@/components/PrintRequest.vue'

export default {
  layout: 'print',
  components: {
    PrintRequest
  },
  computed: {
    ids() {
      // Handle ?ids=1,2,3 for batch printing
      if (this.$route.query.ids) {
        return this.$route.query.ids.split(',');
      }
      // Handle legacy single printing ?id=1
      if (this.$route.query.id) {
        return [this.$route.query.id];
      }
      return [];
    }
  }
}
</script>

<style>
@media print {
  .page-break-after {
    page-break-after: always;
  }
  .page-break-after:last-child {
    page-break-after: auto;
  }
}
</style>