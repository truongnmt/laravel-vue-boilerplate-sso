export default {
  url: 'https://github.com/login/oauth/authorize',
  params: {
    client_id: 'cc91e96004f2ebc00cae',
    redirect_uri: function() { return this.options.getUrl() + '/sso/callback' },
    response_type: 'code',
    scope: 'user_email read:user',
  },
}
