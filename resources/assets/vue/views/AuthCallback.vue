<!-- /* L1-ログイン */-->
<script lang="ts">
import {Component, Vue} from 'vue-property-decorator';
import {Action} from 'vuex-class';

@Component
export default class AuthCallback extends Vue {
    @Action loadData;
    @Action setMenu;
    @Action setDialogMessage;
    @Action setLoading;

    state = {
      rememberMe: String,
    };
    code: string;

    mounted() {
      if (this.$route.query.state) {
        if (typeof this.$route.query.state === 'string')
        this.state = JSON.parse(this.$route.query.state);
      }
      if (this.$route.query.code) {
        if (typeof this.$route.query.code === 'string')
          this.code = this.$route.query.code;
      }
      console.log(this.state.hasOwnProperty('rememberMe') ? this.state.rememberMe : true);
      this.$auth.oauth2({
        code: true,
        provider: 'github',
        params: {
          code: this.code,
          rememberMe: true,
          staySignedIn: true,
          // rememberMe: this.state.hasOwnProperty('rememberMe') ? this.state.rememberMe : true,
        },
        state: {
          rememberMe: true,
          staySignedIn: true,
        },
        success: function(res) {
          console.log('success')
        },
        error: function(res) {
          console.log('error')
        },
      });
    }
}
</script>

<template lang="pug">

</template>

<style scoped>

</style>
