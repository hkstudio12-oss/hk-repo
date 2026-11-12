<script>
import { getToken } from '@/utils/request.js'

export default {
  onLaunch() {
    // Route to the right first screen based on auth state.
    const token = getToken()
    const target = token ? '/pages/home/home' : '/pages/login/login'
    uni.reLaunch({ url: target })
  },
}
</script>

<style>
/* Global resets + dark base. Page-level styles live in each .vue file. */
page {
  background-color: #0b0c0e;
  color: #f4f1ea;
  font-family: -apple-system, 'Inter Tight', 'Helvetica Neue', Roboto, system-ui, sans-serif;
}

view,
text,
button,
input,
scroll-view {
  box-sizing: border-box;
}

/* Kill the default button chrome so our styled buttons look clean. */
button {
  margin: 0;
  padding: 0;
  background: transparent;
  line-height: normal;
  border-radius: 0;
}
button::after {
  border: none;
}
</style>
