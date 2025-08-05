export const generateReqId = async (db: any) => {
  const prefix = 'HR-MPR00';
  const today = new Date();
  const year = today.getFullYear().toString().slice(-2);
  const month = (today.getMonth() + 1).toString().padStart(2, '0');
  const yearMonth = year + month;

  // Query the latest req_id for current year-month
  const latestReq = await db.collection('your_collection')
    .where('req_id', '>=', `${prefix}-${yearMonth}`)
    .where('req_id', '<', `${prefix}-${yearMonth}-9999`)
    .orderBy('req_id', 'desc')
    .limit(1)
    .get();

  let sequence = 1;
  if (!latestReq.empty) {
    const lastReqId = latestReq.docs[0].data().req_id;
    sequence = parseInt(lastReqId.slice(-4)) + 1;
  }

  return `${prefix}-${yearMonth}-${sequence.toString().padStart(4, '0')}`;
};
