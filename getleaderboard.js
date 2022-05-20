let url = 'https://commonvoice.mozilla.org/api/v1/ca/clips/leaderboard?cursor=1';

fetch(url)
.then(res => res.json())
.then(out =>
  console.log('Checkout this JSON! ', out))
.catch(err => console.log(err));