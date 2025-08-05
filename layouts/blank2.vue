<template>
  <v-app>
    <notifications position="bottom right" animation-type="velocity" />
    <v-main style="background: url(bg4.jpg) top center no-repeat; background-size: cover;"> 
      <v-container class="fill-height" fluid>
        <nuxt />
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
export default {
  data () {
    return {
      
    }
  }
}
</script>
